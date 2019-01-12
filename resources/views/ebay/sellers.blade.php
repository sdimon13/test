@extends('ebay/layouts/app')

@section('content')
    <div class="container-fluid">
        <div class="panel panel-default">
            <div class="panel-heading">
                Продавцы
            </div>
            <form action="{{route('sellers')}}">
                <label for="positive_feedback_percent">Процент положительных отзывов >= </label>
                <input type="text" name="positive_feedback_percent" id="positive_feedback_percent" value="{{ app('request')->input('positive_feedback_percent') }}">

                <label for="feedback_score">Количество отзывов >= </label>
                <input type="number" name="feedback_score" id="feedback_score" value="{{ app('request')->input('feedback_score') }}">

                <label for="keywords">Ключевик </label>
                <input type="text" name="keywords" id="keywords" value="{{ app('request')->input('keywords') }}">

                <label for="country">Страна </label>
                <input type="text" name="country" id="country" value="{{ app('request')->input('country') }}">

                <input type="submit">
            </form>
            <div class="panel-body">
                {{--<form action="{{route('customers.store')}}" method="post" enctype="multipart/form-data" id="importFrm" >
                    <input type="hidden" name="MAX_FILE_SIZE" value="30000" />
                    {{ csrf_field() }}
                    <input type="file" name="tableDataFile[]" accept=".csv,.xml" multiple />
                    <input type="submit" class="btn btn-primary" name="importSubmit" value="IMPORT">
                </form>--}}

                @if ($errors->has('tableDataFile'))
                    <span class="bg-danger">
                                        <strong>{{ $errors->first('tableDataFile') }}</strong>
                                    </span>
                @endif
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Имя</th>
                        <th>Количество продуктов по запросу</th>
                        <th>Количество отзывов</th>
                        <th>Процент положительных отзывов</th>
                        <th>Рейтинг отзывов</th>
                        <th>Рейтинг топ продавцов</th>
                        <th>Страна</th>
                        <th>Дата регистрации</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($sellers as $seller)
                        <tr>
                            <th scope="row">{{$seller->id}}</th>
                            <td><a href="https://www.ebay.com/usr/{{$seller->user_name}}" target="blank">{{$seller->user_name}}</a></td>
                            <td>{{$seller->products_count}}</td>
                            <td>{{$seller->feedback_score}}</td>
                            <td>{{$seller->positive_feedback_percent}}</td>
                            <td>{{$seller->feedback_rating_star}}</td>
                            <td>{{$seller->top_rated_seller}}</td>
                            <td>{{$seller->country}}</td>
                            <td>{{$seller->date_reg}}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center"><h2>Данные отсутствуют</h2></td>
                        </tr>
                    @endforelse
                    </tbody>
                    <tfoot>
                    <tr>
                        @if ($sellers->total() > 10)
                        <td>
                            <i>{{$sellers->perPage()}} / {{$sellers->total()}}</i>
                        </td>
                        <td colspan="2">
                            <ul class="pagination pull-right">
                                {{$sellers->links()}}
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

