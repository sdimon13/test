@extends('ebay/layouts/app')

@section('content')
    <div class="container-fluid">

        <div class="panel panel-default">
            <div class="panel-heading">
                Товары
            </div>
            <form action="{{route('products')}}">
                <label for="keywords">Ключевое слово </label>
                <input type="text" name="keywords" id="keywords" value="{{ app('request')->input('keywords') }}">

                <label for="brand">Бренд </label>
                <input type="text" name="brand" id="brand" value="{{ app('request')->input('brand') }}">

                <label for="min_price">Цена >= </label>
                <input type="text" name="min_price" id="min_price" value="{{ app('request')->input('min_price') }}">

                <label for="max_price">Цена <= </label>
                <input type="text" name="max_price" id="max_price" value="{{ app('request')->input('max_price') }}">

                <label for="quantity">Кол доступно=> </label>
                <input type="text" name="quantity" id="quantity" value="{{ app('request')->input('quantity') }}">
<hr>
                <label for="quantity_sold">Кол продано=> </label>
                <input type="text" name="quantity_sold" id="quantity_sold" value="{{ app('request')
                ->input('quantity_sold') }}">

                <label for="shippings_cost">Доставка цена <= </label>
                <input type="text" name="shippings_cost" id="shippings_cost" value="{{ app('request')
                ->input('shippings_cost') }}">

                <label for="shippings_time_max">Доставка дней <= </label>
                <input type="text" name="shippings_time_max" id="shippings_time_max" value="{{ app('request')
                ->input('shippings_time_max') }}">

                <label for="handling_time">Сборка товара дней <= </label>
                <input type="text" name="handling_time" id="handling_time" value="{{ app('request')
                ->input('handling_time') }}">

                <label for="country">Страна</label>
                <input type="text" name="country" id="country" value="{{ app('request')->input('country') }}">

                <label for="variation">Вариации</label>
                <input type="text" name="variation" id="variation" value="{{ app('request')->input('variation') }}">
<hr>
                <label for="seller_id">Id Продавца</label>
                <input type="number" name="seller_id" id="seller_id" value="{{ app('request')->input('seller_id') }}">

                <input type="submit">
            </form>
            <div class="panel-body">


                @if ($errors->has('tableDataFile'))
                    <span class="bg-danger">
                                        <strong>{{ $errors->first('tableDataFile') }}</strong>
                                    </span>
                @endif
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>Ид</th>
                        <th>Имя</th>
                        <th>Описание</th>
                        <th>Бренд</th>
                        <th>Цена</th>
                        <th>Кол. доступно</th>
                        <th>Кол. Продано</th>
                        <th>Global_id</th>
                        <th>Местонахождение</th>
                        <th>Страна</th>
                        <th>Доставка цена</th>
                        <th>Доставка дней</th>
                        <th>Сборка товара(дней)</th>
                        <th>Состояние товара</th>
                        <th>Фото</th>
                        <th>Вариации</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($products as $product)
                        <tr>
                            <th scope="row">{{$product->id}}</th>
                            <td>{{$product->title}}</td>
                            <td>{{$product->description}}</td>
                            <td>{{$product->brand}}</td>
                            <td>{{$product->price}}</td>
                            <td>{{$product->quantity}}</td>
                            <td>{{$product->quantity_sold}}</td>
                            <td>{{$product->global_id}}</td>
                            <td>{{$product->location}}</td>
                            <td>{{$product->country}}</td>
                            <td>{{$product->shippings[0]->cost}}</td>
                            <td>{{$product->shippings[0]->time_max}}</td>
                            <td>{{$product->handling_time}}</td>
                            <td>{{$product->condition_name}}</td>
                            <td><img width="100px" src="{{$product->main_photo}}" /></td>
                            <td>{{$product->variation}}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center"><h2>Данные отсутствуют</h2></td>
                        </tr>
                    @endforelse
                    </tbody>
                    <tfoot>
                    <tr>
                        @if ($products->total() > 10)
                        <td>
                            <i>{{$products->perPage()}} / {{$products->total()}}</i>
                        </td>
                        <td colspan="2">
                            <ul class="pagination pull-right">
                                {{$products->links()}}
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

