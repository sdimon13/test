@extends('app')

@section('content')
    <div class="container">
        <div class="panel panel-default">
            <div class="panel-heading">
                Members list
            </div>
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
                            <td>{{$seller->user_name}}</td>
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

