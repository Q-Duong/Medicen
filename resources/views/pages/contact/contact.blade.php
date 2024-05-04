@extends('layout_not_slider')
@section('content')
@section('title', 'Contact - ')
<!-- Blog Details Hero Begin -->
<!-- Map Begin -->
<div class="map">
    {!!$contact->info_map!!}
</div>
<!-- Map End -->

<!-- Contact Section Begin -->
<section class="contact spad">
    <div class="container">
        <div class="row">
            <div class="col-lg-6 col-md-6">
                <div class="contact__text">
                    <div class="section-title">
                        <span>Thông tin</span>
                        <h2>Liên hệ với chúng tôi</h2>
                        <p>Khi cần trợ giúp vui lòng gọi <span>098 289 6642 (Ms.Mai)</span> hoặc <span>028 36208731</span> (8h30 - 17h)</p>
                    </div>
                    <ul>
                        <li>
                            {!!$contact->info_contact!!}
                        </li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-6 col-md-6">
                <div class="contact__form">
                    <form action="#">
                        <div class="row">
                            <div class="col-lg-6">
                                <input type="text" placeholder="Name">
                            </div>
                            <div class="col-lg-6">
                                <input type="text" placeholder="Email">
                            </div>
                            <div class="col-lg-12">
                                <textarea placeholder="Message"></textarea>
                                <button type="submit" class="site-btn">Send Message</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Contact Section End -->
@endsection