@extends('layouts.app')

@section('content')

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Products</h1>
    </div>

    <div class="card">
        <form action="" method="get" class="card-header">
            <div class="form-row justify-content-between">
                <div class="col-md-2">
                    <input type="text" value="{{ request()->get('title') }}" name="title" placeholder="Product Title" class="form-control">
                </div>
                <div class="col-md-2">
                    <select name="variant" class="form-control">
                        <option value="">Select</option>
                        @foreach($variant as $key => $value)
                            <optgroup label="{{ $value->title }}">
                                @foreach($value->product_variant as $key => $product_variant)
                                <option value="{{ request()->get('variant') }}">{{ $product_variant->variant }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Price Range</span>
                        </div>
                        <input type="text" name="price_from" value="{{ request()->get('price_from') }}" aria-label="First name" placeholder="From"
                               class="form-control">
                        <input type="text" name="price_to" value="{{ request()->get('price_to') }}" aria-label="Last name" placeholder="To" class="form-control">
                    </div>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date" value="{{ request()->get('date') }}" placeholder="Date" class="form-control">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary float-right"><i class="fa fa-search"></i></button>
                </div>
            </div>
        </form>

        <div class="card-body">
            <div class="table-response">
                <table class="table table-bordered">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Variant</th>
                        <th width="150px">Action</th>
                    </tr>
                    </thead>

                    <tbody>
                    @foreach($products as $key => $product)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $product->title }} <br> Created at : {{$product->created_at->format('d-M-y')}}</td>
                            <td>{{ strlen($product->description) > 150 ? substr($product->description, 0, 150).'...' : $product->description }}</td>
                            <td>
                                <dl class="row mb-0" style="height: 80px; overflow: hidden" id="variant{{$key}}">
                                    @foreach($product->product_variation_price as $value)
                                        <dt class="col-sm-3 pb-0">
                                            {{ isset($value->product_variant_one_data) ? $value->product_variant_one_data->variant : '' }}/
                                            {{ isset($value->product_variant_two_data) ? $value->product_variant_two_data->variant : '' }}/
                                            {{ isset($value->product_variant_three_data) ? $value->product_variant_three_data->variant : '' }}
                                        </dt>
                                        <dd class="col-sm-9">
                                            <dl class="row mb-0">
                                                <dt class="col-sm-4 pb-0">Price
                                                    : {{ number_format($value->price, 2) }}</dt>
                                                <dd class="col-sm-8 pb-0">InStock
                                                    : {{ number_format($value->stock, 2) }}</dd>
                                            </dl>
                                        </dd>
                                    @endforeach
                                </dl>
                                <button onclick="$('#variant{{$key}}').toggleClass('h-auto')"
                                        class="btn btn-sm btn-link">Show more
                                </button>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('product.edit', 1) }}" class="btn btn-success">Edit</a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

        </div>

        <div class="card-footer">
            <div class="row">
                <div class="col-md-6">
                    <p>Showing {{ $products->firstItem() }} to {{ $products->lastItem() }} out of {{ $products->total() }}</p>
                </div>
                <div class="col-md-6 text-right">
                    <div class="paginate d-flex justify-content-end my-1">
                        {{ $products->onEachSide(3)->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
