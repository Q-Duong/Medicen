<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    
    <!-- Trang chủ -->
    <url>
        <loc>{{ route('home.index') }}</loc>
        <lastmod>{{ now()->tz('Asia/Ho_Chi_Minh')->toAtomString() }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>

    <!-- Trang Giới thiệu -->
    <url>
        <loc>{{ route('about.index') }}</loc>
        <lastmod>{{ now()->tz('Asia/Ho_Chi_Minh')->toAtomString() }}</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>

    <!-- Trang Dịch vụ (Xe X-quang, AI) -->
    <url>
        <loc>{{ route('services.index') }}</loc>
        <lastmod>{{ now()->tz('Asia/Ho_Chi_Minh')->toAtomString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>

    <!-- Trang Liên hệ -->
    <url>
        <loc>{{ route('contact.index') }}</loc>
        <lastmod>{{ now()->tz('Asia/Ho_Chi_Minh')->toAtomString() }}</lastmod>
        <changefreq>yearly</changefreq>
        <priority>0.7</priority>
    </url>

    <!-- Trang Tin tức -->
    <url>
        <loc>{{ route('blogs.index') }}</loc>
        <lastmod>{{ now()->tz('Asia/Ho_Chi_Minh')->toAtomString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.6</priority>
    </url>

</urlset>