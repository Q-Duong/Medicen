@extends('layout_not_slider')
@section('content')
<div class="container">
    <div class="row">
        <div class="col-lg-12">
            <div class="breadcrumb__text">
                <h4>Sản phẩm</h4>
                <div class="breadcrumb__links">
                    <a href="{{URL::to('/')}}">Trang chủ</a>
                    <span>Sản phẩm</span>
                </div>
            </div>
        </div>
    </div>
</div>
</section>
<!-- Breadcrumb Section End -->

<!-- Shop Section Begin -->
<section class="shop spad">
    <div class="container">
        <div class="row">
            <div class="col-lg-3">
                <div class="shop__sidebar">
                    <div class="shop__sidebar__search">
                        <form action="#">
                            <input type="text" placeholder="Search...">
                            <button type="submit"><span class="icon_search"></span></button>
                        </form>
                    </div>
                    <div class="shop__sidebar__accordion">
                        <div class="accordion" id="accordionExample">
                            <div class="card">
                                <div class="card-heading">
                                    @foreach($category_name as $key => $name)
                                    <a data-toggle="collapse" data-target="#collapseOne">Danh mục
                                        {{$name->category_name}}</a>
                                    @endforeach
                                </div>
                                <div id="collapseOne" class="collapse show" data-parent="#accordionExample">
                                    <div class="card-body">
                                        <div class="shop__sidebar__categories">
                                            <ul class="nice-scroll">
                                                @foreach($category_name as $key => $cate)
                                                @if($cate->category_parent==0)
                                                @foreach($category as $key => $cate_sub)
                                                @if($cate_sub->category_parent == $cate->category_id)
                                                <li><a
                                                        href="{{asset(URL::to('/danh-muc-san-pham/'.$cate_sub->category_product_slug))}}">{{$cate_sub->category_name}}</a>
                                                </li>
                                                @endif
                                                @endforeach
                                                @endif
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-9">
                <div class="shop__product__option">
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-6">
                            <div class="shop__product__option__left">
                                <p>Showing 1–12 of 126 results</p>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-6">
                            <div class="shop__product__option__right">
                                <p>Sort by Price:</p>
                                <select>
                                    <option value="">Low To High</option>
                                    <option value="">$0 - $55</option>
                                    <option value="">$55 - $100</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">

                    @foreach($category_by_id as $key => $product)
                    <div class="col-lg-4 col-md-6 col-sm-6">
                        <div class="product__item">
                            <div class="product__item__pic set-bg"
                                data-setbg="{{URL::to('uploads/product/'.$product->product_image)}}">
                                <span class="label">{{$product->product_status}}</span>
                                <ul class="product__hover">
                                    <li><a href="#"><img src="{{asset('frontend/img/icon/heart.png')}}"
                                                alt=""></a></li>
                                    <li><a href="#"><img src="{{asset('frontend/img/icon/compare.png')}}" alt="">
                                            <span>Compare</span></a></li>
                                    <li><a href="{{URL::to('/chi-tiet-san-pham/'.$product->product_slug)}}"><img
                                                src="{{asset('frontend/img/icon/search.png')}}" alt=""></a>
                                    </li>
                                </ul>
                            </div>
                            <form>
                                {{ csrf_field() }}
                                <input type="hidden" value="{{$product->product_id}}"
                                    class="cart_product_id_{{$product->product_id}}">

                                <input type="hidden" value="{{$product->product_name}}"
                                    class="cart_product_name_{{$product->product_id}}">

                                <input type="hidden" value="{{$product->product_quantity}}"
                                    class="cart_product_quantity_{{$product->product_id}}">

                                <input type="hidden" value="{{$product->product_image}}"
                                    class="cart_product_image_{{$product->product_id}}">

                                <input type="hidden" value="{{$product->product_price}}"
                                    class="cart_product_price_{{$product->product_id}}">

                                <input type="hidden" value="1" class="cart_product_qty_{{$product->product_id}}">

                                <div class="product__item__text">
                                    <h6>{{$product->product_name}}</h6>
                                    <a type="button" data-id_product="{{$product->product_id}}" name="add-to-cart"
                                        class="add-cart add-to-cart">+ Add To Cart</a>

                                    <div class="rating">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <h5> {{number_format($product->product_price).'₫'}}</h5>
                                    <div class="product__color__select">
                                        <label for="pc-1">
                                            <input type="radio" id="pc-1">
                                        </label>
                                        <label class="active black" for="pc-2">
                                            <input type="radio" id="pc-2">
                                        </label>
                                        <label class="grey" for="pc-3">
                                            <input type="radio" id="pc-3">
                                        </label>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    @endforeach
                </div>
                <!-- <div class="row">
                    <div class="col-lg-12">
                        <div class="product__pagination">
                            <a class="active" href="#">1</a>
                            <a href="#">2</a>
                            <a href="#">3</a>
                            <span>...</span>
                            <a href="#">21</a>
                        </div>
                    </div>
                </div> -->
            </div>
        </div>
    </div>
</section>
@endsection