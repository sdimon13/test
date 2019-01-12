@extends('ebay/layouts/app')

@section('content')
    <div class="container-fluid">

        <div class="panel panel-default">
            <div class="panel-heading">
                Товары
            </div>
            <form action="{{route('checkCount')}}">
                <label for="keywords">Ключевое слово </label>
                <input type="text" name="keywords" id="keywords" value="{{ app('request')->input('keywords') }}">

                <label for="min_price">Мин. цена </label>
                <input type="text" name="min_price" id="min_price" value="{{ app('request')->input('min_price') }}">

                <label for="max_price">Макс. Цена </label>
                <input type="text" name="max_price" id="max_price" value="{{ app('request')->input('max_price') }}">

                <label for="feedback_score_min">Мин. количество отзывов</label>
                <input type="text" name="feedback_score_min" id="feedback_score_min" value="{{ app('request')
                ->input('feedback_score_min') }}">

                <label for="feedback_score_max">Макс. количество отзывов</label>
                <input type="text" name="feedback_score_max" id="feedback_score_max" value="{{ app('request')
                ->input('feedback_score_max') }}">

                <input type="submit" name="submit" value="check">
                <input type="submit" name="submit" value="send">

               
            </form>
            <div class="panel-body">

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                    @if (isset($totalEntries))
                        <div class="alert alert-danger">
                            <ul>
                                    <li>{{ $totalEntries }}</li>
                            </ul>
                        </div>
                    @endif
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>Ид</th>
                        <th>Имя</th>
                        <th>Мин. цена</th>
                        <th>Макс. цена</th>
                        <th>Мин. количество отзывов</th>
                        <th>Макс. количество отзывов</th>
                        <th>Всего товаров</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($keywords as $keyword)
                        <tr>
                            <th scope="row">{{$keyword->id}}</th>
                            <td>{{$keyword->name}}</td>
                            <td>{{$keyword->min_price}}</td>
                            <td>{{$keyword->max_price}}</td>
                            <td>{{$keyword->feedback_score_min}}</td>
                            <td>{{$keyword->feedback_score_max}}</td>
                            <td>{{$keyword->total_products}}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center"><h2>Данные отсутствуют</h2></td>
                        </tr>
                    @endforelse
                    </tbody>
                    <tfoot>
                    <tr>
                        @if ($keywords->total() > 10)
                        <td>
                            <i>{{$keywords->perPage()}} / {{$keywords->total()}}</i>
                        </td>
                        <td colspan="2">
                            <ul class="pagination pull-right">
                                {{$keywords->links()}}
                            </ul>
                        </td>
                        @endif
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection

