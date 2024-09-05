[cloudflare](https://developers.cloudflare.com/)提供了R2一定配额的免费存储空间，以及开箱即用的CDN功能，同时R2支持Amazon S3的编程特性。

我在使用R2的时候，有一个开放给访客的图片上传功能，大多数人现在上传内容都是从手机相册直接选取图片，这些图片的体积动辄5-10M之间哎，但事实上我们在web端根本就不需要这么大体积的图片。所以，我想能不能在客户浏览器端上传之前将图片体积压缩，并且直接转为webP格式。

代码特别简单，R2是控制器，view是视图，逻辑也非常简单，后端就是请求生成预上传url和保存数据提交。

2. Compress Videos Before Upload
To compress videos, you can use ffmpeg.wasm:

## Solution Overview
**Compress Images:** Use libraries like `browser-image-compression` or the canvas API for image compression.
**Compress Videos:** Use libraries like `ffmpeg.wasm` for video compression directly in the browser.

Implementation
1. Compress Images Before Upload
Here's how to compress images using the browser-image-compression library:
Include the browser-image-compression library:
`<script src="https://cdn.jsdelivr.net/npm/browser-image-compression@latest/dist/browser-image-compression.js"></script>`
2. Compress Videos Before Upload
To compress videos, you can use ffmpeg.wasm:
`<script src="https://cdn.jsdelivr.net/npm/@ffmpeg/ffmpeg@latest"></script>`
3. Convert Images to WebP Before Upload
To achieve this, you can use the browser's canvas API to convert images to WebP format. Here's an updated implementation for handling image uploads.

## Explanation:
**Image Compression:** The browser-image-compression library reduces the image size before uploading.
**Video Compression:** ffmpeg.wasm allows video compression directly in the browser. The example uses FFmpeg to encode videos with a lower bitrate.

## Benefits:
**Faster Uploads:** Smaller file sizes lead to faster uploads.
**Reduced Storage Costs:** Compressed files take up less space in Cloudflare R2.
**Better User Experience:** Faster uploads and reduced data usage for users.

Additional Considerations:
**Browser Compatibility:** Ensure that the user's browser supports WebP and the necessary APIs (canvas, FileReader).
**Quality Settings:** Adjust the canvas.toBlob quality parameter (0.8 in the example) as needed to balance image quality and file size.
