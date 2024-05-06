@extends('layout_not_slider')
@section('content')
@section('title', ' - ')
@foreach($product_details as $key => $value)

<section class="shop-details">
    <div class="product__details__pic">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="product__details__breadcrumb">
                        <a href="{{URL::to('/')}}">Trang chủ</a>
                        <a href="{{url('/product-list/'.$cate_slug)}}">{{$product_cate}}</a>
                        <span>{{$meta_title}}</span>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-7 col-md-7">
                    <ul id="imageGallery">
                        @foreach($gallery as $key => $gal)
                        <li style="width:100%; margin:auto; "
                            data-thumb="{{asset('uploads/gallery/'.$gal->gallery_image)}}"
                            data-src="{{asset('uploads/gallery/'.$gal->gallery_image)}}">
                            <img width="100%" src="{{asset('uploads/gallery/'.$gal->gallery_image)}}"
                                alt="{{$gal->gallery_name}}" />
                        </li>
                        @endforeach
                    </ul>
                </div>
                <div class="col-lg-1 d-none d-lg-block"> </div>
                <div class="col-lg-4 col-md-5">
                    <div class="product__details__text">
                        <h3>{{$value->product_name}}</h3>
                        <div class="rating ">
                            <div class="row d-flex ">
                                <ul class="list-inline">
                                    @for($count=1; $count<=5; $count++) @php if($count<=$rating){
                                        $color='color:#ffcc00;' ; } else { $color='color:#ccc;' ; } @endphp <li
                                        title="star_rating" data-product_id="{{$value->product_id}}"
                                        data-rating="{{$rating}}" class="rating"
                                        style="cursor:pointer; {{$color}} font-size:30px;">&#9733;</li>
                                        @endfor
                                </ul>
                            </div>
                        </div>
                        <h5>{{number_format($value->product_price,0,',','.').'₫'}}</h5>
                        <span>Danh mục:</span> {{$value->category_product_name}}
                        
                        <div class="product__details__option">
                                <!-- <div class="product__details__option__size">
                                    <span>Size:</span>
                                    <label for="xxl">xxl
                                        <input type="radio" id="xxl">
                                    </label>
                                    <label class="active" for="xl">xl
                                        <input type="radio" id="xl">
                                    </label>
                                    <label for="l">l
                                        <input type="radio" id="l">
                                    </label>
                                    <label for="sm">s
                                        <input type="radio" id="sm">
                                    </label>
                                </div> -->
                                <div class="product__details__option__color">
                                    <span>Màu:</span>
                                    <label class="c-1" for="sp-1">
                                        <input type="radio" id="sp-1">
                                    </label>
                                    <label class="c-2" for="sp-2">
                                        <input type="radio" id="sp-2">
                                    </label>
                                    <label class="c-3" for="sp-3">
                                        <input type="radio" id="sp-3">
                                    </label>
                                    <label class="c-4" for="sp-4">
                                        <input type="radio" id="sp-4">
                                    </label>
                                    <label class="c-9" for="sp-9">
                                        <input type="radio" id="sp-9">
                                    </label>
                                </div>
                            </div>
                        <form>
                            {{ csrf_field() }}
                                <a id="wishlist_producturl{{$value->product_id}}" href="{{URL::to('/product/'.$value->product_slug)}}"></a>

                                <input type="hidden" value="{{$value->product_id}}"
                                    class="cart_product_id_{{$value->product_id}}">

                                <input type="hidden" value="{{$value->product_slug}}"
                                    class="cart_product_slug_{{$value->product_id}}">

                                <input type="hidden" id="wishlist_productname{{$value->product_id}}"
                                    value="{{$value->product_name}}" class="cart_product_name_{{$value->product_id}}">

                                <input type="hidden" value="{{$value->product_quantity}}"
                                    class="cart_product_quantity_{{$value->product_id}}">

                                <input type="hidden" value="{{$value->product_image}}"
                                    class="cart_product_image_{{$value->product_id}}">

                                <input type="hidden" id="wishlist_productprice{{$value->product_id}}"
                                    value="{{number_format($value->product_price,0,',','.')}}₫">

                                <input type="hidden" value="{{$value->product_price}}"
                                    class="cart_product_price_{{$value->product_id}}">

                                <img id="wishlist_productimage{{$value->product_id}}"
                                    src="{{URL::to('uploads/product/'.$value->product_image)}}"
                                    style="display:none;" />

                                <input type="hidden" value="1" class="cart_product_qty_{{$value->product_id}}">

                                <div class="product__details__cart__option">
                                    @if($value->product_quantity > 0)
                                    <button type="button" class="primary-btn-add add-to-cart"
                                        data-id_product="{{$value->product_id}}" name="add-to-cart">
                                        <i class="fa fa-shopping-cart"></i>Thêm vào giỏ hàng
                                    </button>
                                    @else
                                    <a class="primary-btn add-to-cart">
                                    <i class="fas fa-store-alt-slash"></i>SOLD OUT
                                    </a>
                                    @endif
                                </div>
                                <div class="product__details__btns__option">
                                    <a type="button" class="primary-btn-wistlist" id="{{$value->product_id}}" onclick="add_wistlist(this.id);"><i
                                            class="fa fa-heart"></i> Yêu thích</a>
                                </div>
                                <div class="product__details__last__option">
                                    <h5><span>Phương thức thanh toán</span></h5>
                                    <img src="{{asset('frontend/img/shop-details/details-payment.png')}}" alt="">
                                    {{-- <ul>
                                        <li><span>Danh mục:</span> {{$value->category_product_name}}</li>
                                        <li><span>Mã sản phẩm:</span> {{$value->product_id}}</li>
                                    </ul> --}}
                                </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="product__details__content">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="product__details__tab">
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" data-toggle="tab" href="#tabs-5" role="tab">Thông số kỹ
                                    thuật</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" href="#tabs-6" role="tab">Đặc điểm nổi bật</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" href="#tabs-7" role="tab">Đánh giá(5)</a>
                            </li>

                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane active" id="tabs-5" role="tabpanel">
                                <div class="product__details__tab__content">
                                    <p class="note">{!!$value->product_parameter!!}</p>
                                </div>
                            </div>
                            <div class="tab-pane" id="tabs-6" role="tabpanel">
                                <div class="product__details__tab__content">
                                    <p class="note"></p>
                                    <div class="product__details__tab__content__item">
                                        <p>{!!$value->product_content!!}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane" id="tabs-7" role="tabpanel">
                                <div class="product__details__tab__content">
                                    <form>
                                        {{ csrf_field() }}
                                        <input type="hidden" name="comment_product_id" class="comment_product_id"
                                            value="{{$value->product_id}}">
                                        <div id="comment_show"></div>

                                    </form>

                                    <div class="h-30 row d-flex justify-content-center">
                                        <div class="col-lg-8 centered">
                                            <div class="blog__details__comment">
                                                <h4>Thêm bình luận</h4>
                                                <!--Rating-->
                                                <h3>Đánh giá & nhận xét</h3>
                                                <ul class="list-inline rating" title="Average Rating">
                                                    @for($count=1; $count<=5; $count++) @php if($count<=$rating){
                                                        $color='color:#ffcc00;' ; } else { $color='color:#ccc;' ; }
                                                        @endphp <li title="star_rating"
                                                        id="{{$value->product_id}}-{{$count}}" data-index="{{$count}}"
                                                        data-product_id="{{$value->product_id}}"
                                                        data-rating="{{$rating}}" class="rating"
                                                        style="cursor:pointer; {{$color}} font-size:30px;">&#9733;</li>
                                                        @endfor
                                                </ul>

                                                <form action="#">
                                                    <div id="notify_comment"></div>
                                                    <div class="row">
                                                        <div class="col-lg-5 col-md-5">
                                                            <input type="text" class="comment_name" placeholder="Name">
                                                        </div>
                                                        <div class="col-lg-12 text-center">
                                                            <textarea class="comment_content"
                                                                placeholder="Comment"></textarea>
                                                            <button type="submit" class="site-btn send-comment">Gửi đánh
                                                                giá</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endforeach
<!-- Shop Details Section End -->

<!-- Related Section Begin -->
<section class="related spad">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <h3 class="related-title">Sản phẩm liên quan</h3>
            </div>
        </div>
        <div class="row">
            @foreach($relate as $key => $lienquan)
            <div class="col-lg-3 col-md-6 col-sm-6 col-sm-6">
                <a class="heart" type="button" id="{{$lienquan->product_id}}" onclick="add_wistlist(this.id);">
                    <i class="far fa-heart"></i>
                </a>
                <div class="product__item">
                    <a id="wishlist_producturl{{$lienquan->product_id}}"
                        href="{{URL::to('/product/'.$lienquan->product_slug)}}">
                        <div class="product__item__pic set-bg"
                            data-setbg="{{URL::to('uploads/product/'.$lienquan->product_image)}}">
                            @if($lienquan->product_status==2)
                            <span class="label">
                                Mới
                            </span>
                            @elseif($lienquan->product_status==1)
                            <span class="label">
                                Khuyến mãi
                            </span>
                            @else
                            @endif

                            @if($lienquan->product_quantity==0)
                            <div class="product_sold_out">
                                <p>Sold out</p>
                            </div>
                            @else
                            @endif
                        </div>
                    </a>
                        <input type="hidden" value="{{$lienquan->product_id}}"
                            class="cart_product_id_{{$lienquan->product_id}}">

                        <input type="hidden" id="wishlist_productname{{$lienquan->product_id}}"
                            value="{{$lienquan->product_name}}" class="cart_product_name_{{$lienquan->product_id}}">

                        <input type="hidden" id="wishlist_productprice{{$lienquan->product_id}}"
                            value="{{number_format($lienquan->product_price,0,',','.')}}₫">

                        <img id="wishlist_productimage{{$lienquan->product_id}}"
                            src="{{URL::to('uploads/product/'.$lienquan->product_image)}}"
                            style="display:none;" />

                    <div class="product__item__text">
                        <h6>{{$lienquan->product_name}}</h6>
                        <!-- <div class="rating">
                            <ul class="list-inline">
                                @for($count=1; $count<=5; $count++)
                                    @php
                                        if($count<=$rating){
                                            $color = 'color:#ffcc00;';
                                        }
                                        else {
                                            $color = 'color:#ccc;';
                                        }
                                    @endphp
                                    <li title="star_rating"  data-product_id="{{$lienquan->product_id}}" data-rating="{{$rating}}" class="rating" style="cursor:pointer; {{$color}} font-size:30px;">&#9733;</li>
                                @endfor
                            </ul>
                        </div> -->
                        <h5>{{number_format($lienquan->product_price,0,',','.').'₫'}}</h5>
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
</section>
<!-- Related Section End -->
@endsection