<div class="container">
    <footer class="footer">
        <div class="row">
            <div class="col-lg-4 col-md-6 col-sm-6">
                <div class="footer__widget">
                    <p>Dịch vụ</p>
                    <ul>
                        @foreach ($getAllService as $key => $service)
                            <li><a href="{{ route('service.details', $service->service_slug) }}">
                                    {{ $service->service_title }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            {{-- <div class="col-lg-4 col-md-6 col-sm-6">
                <div class="footer__widget">
                    <p>Tin tức</p>
                    <ul>
                        @foreach ($getAllBlogCategory as $key => $blogCategory)
                            <li><a
                                    href="{{ Route('blog_category.index', $blogCategory->blog_category_slug) }}">{{ $blogCategory->blog_category_name }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div> --}}
            <div class="col-lg-4 col-md-12 col-sm-12">
                <div class="footer__widget">
                    <p>Thông tin liên hệ </p>
                    <div class="footer__newslatter">
                        <p>Hotline:<a href="tel:0987087230"> 0987.087.230 (Ms.Quyên)</a></p>
                        <p>Email:<a href="mailto:medicen.company@gmail.com"> medicen.company@gmail.com</a></p>
                        <p>Địa chỉ:<a target="_blank" href="https://www.google.com/maps/dir//C%C3%94NG+TY+TNHH+%C4%90%E1%BA%A6U+T%C6%AF+V%C3%80+TRANG+THI%E1%BA%BET+B%E1%BB%8A+Y+T%E1%BA%BE+NAM+KH%C3%81NH+LINH+S%E1%BB%91+59+%C4%90%C6%B0%E1%BB%9Dng+s%E1%BB%91+9+B%C3%ACnh+H%C6%B0ng+B%C3%ACnh+Ch%C3%A1nh,+Th%C3%A0nh+ph%E1%BB%91+H%E1%BB%93+Ch%C3%AD+Minh/@10.719258,106.660787,16z/data=!4m8!4m7!1m0!1m5!1m1!1s0x31752e3bc7e767e5:0x169aca0c83b94fdb!2m2!1d106.660787!2d10.719258?entry=ttu">59 Đường số 9, KDC Nam Sài Gòn - Thế Kỷ 21, Xã Bình Hưng, Huyện Bình Chánh, TP.HCM <span class="link-maps"> ( Xem bản đồ )</span></a></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="footer__buttom">
            </div>
            <div class="col-12">
                <div class="footer__copyright__text">
                    <p>Copyright © 2023 Medicen. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>
</div>