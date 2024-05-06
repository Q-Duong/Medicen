@extends('layout_not_slider')
@section('content')
@section('title', 'All Products - ')
<section>
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
<div class="container">
    <div class="category_product">
    @foreach($category as $key => $cate)
        <a href="{{asset(URL::to('/product-list/'.$cate->category_product_slug))}}">
            <div class="category_product_item">
                <img width="100%" src="{{asset('uploads/categoryproduct/'.$cate->category_product_image)}}">
                <p>
                    {{$cate->category_product_name}}
                </p>
            </div>
        </a>
    @endforeach
    </div> 
</div>



<!-- Shop Section Begin -->
<section class="shop spad">
    <div class="container">
        <div class="row">
            <div class="col-lg-3">
                <div class="shop__sidebar">
                    <div class="shop__sidebar__search">
                    </div>
                    <div class="shop__sidebar__accordion">
                        <div class="accordion" id="accordionExample">
                            <div class="card">
                                <div class="card-heading">
                                    <a data-toggle="collapse" data-target="#collapseOne">Danh mục sản phẩm</a>
                                </div>
                                <div id="collapseOne" class="collapse show" data-parent="#accordionExample">
                                    <div class="card-body">
                                        <div class="shop__sidebar__categories">
                                            <ul class="nice-scroll">
                                                @foreach($category as $key => $cate)
                                                <li><a
                                                        href="{{asset(URL::to('/product-list/'.$cate->category_product_slug))}}">{{$cate->category_product_name}}</a>
                                                </li>
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
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-6">
                            <div class="shop__product__option__right">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    @foreach($all_product as $key => $product)
                    <div class="col-lg-4 col-md-6 col-sm-6">
                        <a class="heart" type="button" id="{{$product->product_id}}" onclick="add_wistlist(this.id);">
                            <i class="far fa-heart"></i>
                        </a>
                        <div class="product__item">
                            <a id="wishlist_producturl{{$product->product_id}}" href="{{URL::to('/product/'.$product->product_slug)}}">
                                <div class="product__item__pic set-bg"
                                    data-setbg="{{URL::to('uploads/product/'.$product->product_image)}}">
                                    @if($product->product_status==2)
                                    <span class="label">
                                        Mới
                                    </span>
                                    @elseif($product->product_status==1)
                                    <span class="label">
                                        Khuyến mãi
                                    </span>
                                    @else
                                    @endif

                                    @if($product->product_quantity==0)
                                    <div class="product_sold_out">
                                        <p>Sold out</p>
                                    </div>
                                    @else
                                    @endif

                                    <!-- <ul class="product__hover">
                                        <li><a type="button" id="{{$product->product_id}}"
                                                onclick="add_wistlist(this.id);"><img
                                                    src="{{asset('frontend/img/icon/heart.png')}}" alt=""><span>Yêu
                                                    thích</span></a></li>
                                        <li><a href="#"><img src="{{asset('frontend/img/icon/compare.png')}}" alt="">
                                                <span>So sánh</span></a></li>
                                        <li><a id="wishlist_producturl{{$product->product_id}}"
                                                href="{{URL::to('/chi-tiet-san-pham/'.$product->product_slug)}}"><img
                                                    src="{{asset('frontend/img/icon/search.png')}}"
                                                    alt=""></a><span>Xem sản phẩm</span></li>
                                    </ul> -->
                                </div>
                            </a>
                            
                                <input type="hidden" value="{{$product->product_id}}"
                                    class="cart_product_id_{{$product->product_id}}">

                                <input type="hidden" id="wishlist_productname{{$product->product_id}}"
                                    value="{{$product->product_name}}"
                                    class="cart_product_name_{{$product->product_id}}">

                                <input type="hidden" id="wishlist_productprice{{$product->product_id}}"
                                    value="{{number_format($product->product_price,0,',','.')}}₫">

                                <img id="wishlist_productimage{{$product->product_id}}"
                                    src="{{URL::to('uploads/product/'.$product->product_image)}}"
                                    style="display:none;" />

                            <div class="product__item__text">
                                <h6>{{$product->product_name}}</h6>
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
                        </div>
                    </div>
                    @endforeach
                </div>
                
            </div>
        </div>
    </div>
    
    {{ $all_product->links('pagination::bootstrap-4') }}
    
</section>
<!-- Shop Section End -->
@endsection