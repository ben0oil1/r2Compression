<p>To compress images or videos on the client side before uploading them, you can use JavaScript libraries that handle media compression directly in the browser. This approach reduces the file size before uploading to Cloudflare R2, improving upload speed and lowering storage costs.</p>

<h2>Solution Overview</h2> 
<p>Compress Images: Use libraries like browser-image-compression or the canvas API for image compression.</p>
<p>Compress Videos: Use libraries like ffmpeg.wasm for video compression directly in the browser.</p>
 

<h2>上传多文件：</h2>
<!--browser-image-compression.js用于前端的 图片压缩，在转化压缩哪里只要修改一下代码即可，如果只是转webp，这个文件不必引用-->
<script src="https://cdn.jsdelivr.net/npm/browser-image-compression@latest/dist/browser-image-compression.js"></script>
<!--ffmpeg.wasm 是用来前端压缩视频的，性能自行评估-->
<script src="https://cdn.jsdelivr.net/npm/@ffmpeg/ffmpeg@latest"></script>


<form id="commentForm09051" method="post" enctype="multipart/form-data">
    <textarea name="comment" placeholder="Enter your comment"></textarea>
    <input type="file" id="images" name="images[]" multiple>
    <button type="submit">Submit</button>
</form>

<script>
    document.getElementById('commentForm09051').addEventListener('submit', async function(event) {
        event.preventDefault();

        const files = document.getElementById('images').files;
        const fileArray = Array.from(files); // Convert FileList to an Array
        const compressedFiles = [];
        const formData = new FormData();
        const uploadedFiles = [];

        for (const file of fileArray) {
            if (file.type.startsWith('image/')) {
                // Compress image file using the method above
                const options = {
                    maxSizeMB: 5, // Maximum size in MB (adjust as needed)
                    maxWidthOrHeight: 3000, // Maximum width or height (adjust as needed)
                    useWebWorker: true // Enable web worker for better performance
                };
 
                // Convert images to WebP format
                try {
                    const webpFile = await convertToWebP(file);
                    compressedFiles.push(webpFile);
                } catch (error) {
                    console.error("Error convert file:", error);
                    return;
                }

                // try {
                //     const compressedFile = await imageCompression(file, options);
                //     compressedFiles.push(file);
                // } catch (error) {
                //     console.error("Error compressing file:", error);
                //     return;
                // }
                //------------------
            } else if (file.type.startsWith('video/')) {
                // Load ffmpeg
                const {
                    createFFmpeg,
                    fetchFile
                } = FFmpeg;
                const ffmpeg = createFFmpeg({
                    log: true
                });
                await ffmpeg.load();
                // Compress video file
                ffmpeg.FS('writeFile', file.name, await fetchFile(file));

                await ffmpeg.run('-i', file.name, '-vcodec', 'libx264', '-crf', '28', 'output.mp4');
                const data = ffmpeg.FS('readFile', 'output.mp4');

                const compressedVideo = new File([data.buffer], 'output.mp4', {
                    type: 'video/mp4'
                });
                compressedFiles.push(compressedVideo);
            }
        }


        // Prepare an array of file data for presigned URL request
        const fileDataArray = compressedFiles.map(file => ({
            name: file.name,
            type: file.type
        }));

        // Get presigned URLs for all files
        const response = await fetch(`/r2/getPresignedUrls`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                files: fileDataArray
            })
        });

        const presignedUrls = await response.json();

        // Upload each file directly to R2
        await Promise.all(compressedFiles.map(async (file, index) => {
            const {
                url,
                fields
            } = presignedUrls[index];

            await fetch(url, {
                method: 'PUT',
                body: file
            });

            uploadedFiles.push(fields.key); // Save the file key to use in comment submission
        }));

        // Prepare form data with the comment and uploaded image keys
        formData.append('comment', document.querySelector('textarea[name="comment"]').value);
        uploadedFiles.forEach(key => formData.append('images[]', key));

        // Save the comment and associated images
        const saveResponse = await fetch('/r2/saveComment', {
            method: 'POST',
            body: formData
        });

        const result = await saveResponse.json();
        alert(result.status);
    });

    async function convertToWebP(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = function(event) {
                const img = new Image();
                img.onload = function() {
                    const MAX_WIDTH = 1000; // Maximum width in pixels
                    const scaleFactor = MAX_WIDTH / img.width;
                    const width = img.width > MAX_WIDTH ? MAX_WIDTH : img.width;
                    const height = img.width > MAX_WIDTH ? img.height * scaleFactor : img.height;

                    const canvas = document.createElement('canvas');
                    canvas.width = width;
                    canvas.height = height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, width, height);

                    // Convert the canvas content to a WebP Blob
                    canvas.toBlob((blob) => {
                        if (blob) {
                            const webpFile = new File([blob], file.name.replace(/\.\w+$/, '.webp'), {
                                type: 'image/webp'
                            });
                            resolve(webpFile);
                        } else {
                            reject(new Error('Failed to convert image to WebP.'));
                        }
                    }, 'image/webp', 0.8); // 0.8 is the quality (0 to 1 scale)
                };
                img.src = event.target.result;
            };
            reader.onerror = reject;
            reader.readAsDataURL(file);
        });
    }
</script>


<hr/>

<h2>上传单一文件：</h2>
<form id="commentForm" method="post" enctype="multipart/form-data">
    <textarea name="comment" placeholder="Enter your comment"></textarea>
    <input type="file" id="image2" name="image">
    <button type="submit">Submit</button>
</form>

<script>
    function blobToFile(theBlob, fileName) {
        //A Blob() is almost a File() - it's just missing the two properties below which we will add
        theBlob.lastModifiedDate = new Date();
        theBlob.name = fileName;
        return theBlob;
    }
    document.getElementById('commentForm').addEventListener('submit', async function(event) {
        event.preventDefault();

        const fileInput = document.getElementById('image2');
        const file = fileInput.files[0];
        const formData = new FormData();
 
        //---------------------------------------------------
        // Get a presigned URL for the file
        const response = await fetch(`/r2/getPresignedUrl?filename=${file.name}&filetype=${file.type}`);
        const {
            url,
            fields
        } = await response.json();

        // Upload the file directly to R2
        const uploadData = new FormData();
        uploadData.append('file', file);

        for (const name in fields) {
            uploadData.append(name, fields[name]);
        }


        await fetch(url, {
            method: 'PUT',
            body: file
        });

        // Now submit the comment with the image URL to your backend
        formData.append('comment', document.querySelector('textarea[name="comment"]').value);
        formData.append('image', fields.key); // Save the file key to your server

        // Save the comment
        const saveResponse = await fetch('/r2/saveComment', {
            method: 'POST',
            body: formData
        });

        const result = await saveResponse.json();
        //~~~~~~~~~~~~~~~~~~~~~~

        // alert(result.status);
    });
</script>
