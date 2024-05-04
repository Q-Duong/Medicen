@extends('layout_index')
@section('content')
<div id="wrapper">
    <div class="hero_text">
        <p>Apple Store</p>
        <h3>Welcome to Apple Store</h3>
        <h5>Please, select a location to continue</h3>
            <div class="select">
                <div class="row">
                    <select name="product_active" class="form-control">
                        <option>Select your Country/Region</option>
                        <option>Vietnamese</option>
                        <option>English</option>
                        <option>Spanish</option>
                        <option>Korean</option>
                    </select>
                    <a href="{{URL::to('/home')}}" type="submit" class="site-btn-index">Shop</a>
                </div>
            </div>
    </div>
</div>
@endsection