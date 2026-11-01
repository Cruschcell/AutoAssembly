<html>
<head>
    <link rel="stylesheet" href="generalstyle.css">
    <style>
        /* Video container styles (horizontal layout) */
        .video-container {
            display: flex;
            justify-content: space-around;
            padding: 20px;
            flex-wrap: wrap;
        }
        .video-pane {
            text-align: center;
            width: 30%;
            min-width: 300px;
            margin-bottom: 20px;
        }
        
        .image-embeds {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            gap: 40px;
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
        }
        .image-embed {
            width: 100%;
            text-align: center;
        }
        .image-embed img {
            width: 100%;
            max-width: 600px;
            height: auto;
            max-height: 800px;
            object-fit: contain;
        }

        .video-label {
            font-size: 18px;
            font-weight: bold;
        }
        .yt-embed-holder {
            width: 100%;
            height: 500px;
            overflow: hidden;
            position: relative;
        }
        .yt-embed-holder iframe {
            position: absolute;
            top: -60px;
            left: 0;
            width: 100%;
            height: calc(100% + 120px); 
            border: none;
            pointer-events: none; 
        }
        .unmute-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.01);
            z-index: 10;
            cursor: pointer;
        }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>    
<body>
    <div class="video-container">
        <div class="video-pane">
            <div class="yt-embed-holder">
                <div class="unmute-overlay"></div>
                <iframe 
                    src="https://www.youtube-nocookie.com/embed/5Qn8zxhHs44?autoplay=1&mute=1&loop=1&controls=0&modestbranding=1&rel=0&playsinline=1&enablejsapi=1&playlist=5Qn8zxhHs44&fs=0" 
                    frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                    allowfullscreen>
                </iframe>
            </div>
            <div class="video-label my-5"><h2>wooJin</h2></div>
        </div>
        <div class="video-pane">
            <div class="yt-embed-holder">
                <div class="unmute-overlay"></div>
                <iframe
                    src="https://www.youtube-nocookie.com/embed/OaMPGbWFaX8?autoplay=1&mute=1&loop=1&controls=0&modestbranding=1&rel=0&playsinline=1&enablejsapi=1&playlist=OaMPGbWFaX8&fs=0" 
                    frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                    allowfullscreen>
                </iframe>
            </div>
            <div class="video-label my-5"><h2>J-Man</h2></div>
        </div>
        <div class="video-pane">
            <div class="yt-embed-holder">
                <div class="unmute-overlay"></div>
                <iframe 
                    src="https://www.youtube-nocookie.com/embed/dj4CinmIn3M?autoplay=1&mute=1&loop=1&controls=0&modestbranding=1&rel=0&playsinline=1&enablejsapi=1&playlist=dj4CinmIn3M&fs=0" 
                    frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                    allowfullscreen>
                </iframe>
            </div>
            <div class="video-label my-5"><h2>Pepper</h2></div>
        </div>
    </div>

    <div class="image-embeds">
        <div class="image-embed">
            <img src="https://pbs.twimg.com/media/GiRcCAMacAAESJ8?format=jpg&name=large">
        </div>
        
        <div class="image-embed">
            <img src="https://i.redd.it/3cxofjagvlve1.jpeg" alt="Image 1">
        </div>
        <div class="image-embed">
            <img src="https://scontent.fmnl17-3.fna.fbcdn.net/v/t39.30808-6/484955099_1195563921927959_5721903385294642657_n.jpg?stp=cp6_dst-jpg_tt6&_nc_cat=103&ccb=1-7&_nc_sid=833d8c&_nc_ohc=2Zh-6VhsygoQ7kNvwExKPcP&_nc_oc=AdkoV7tAlyIZcCfFnGD9McAbnnkhjA-Jhgo8tHn31OcF0CIcn-9oFXFPxpRFzAbrHrw&_nc_zt=23&_nc_ht=scontent.fmnl17-3.fna&_nc_gid=PsTcnRcmioRNnN5Ppazuxg&oh=00_AfEiaWTfrufe4QvZQ0LSE66WW_CHVmSOa-1ph_UjbTi3Dw&oe=681990C3">
        </div>
        <div class="image-embed">
            <img src="https://static-cdn.toi-media.com/www/uploads/2012/08/2000px-Mossad_seal.svg_.png">
        </div>
        <div class="image-embed">
            <img src="https://pbs.twimg.com/media/Go-QvxlbYAAz6Zk?format=jpg&name=small">
        </div>
        <div class="image-embed">
            <img src="https://pbs.twimg.com/media/Gpx10zvWkAAyqsN?format=jpg&name=large">
        </div>
        <div class="image-embed">
            <img src="https://pbs.twimg.com/media/GimDNUqbYAAYelV?format=jpg&name=small"> 
        </div>
        <div class="image-embed">
            <img src="https://preview.redd.it/erm-what-the-sigma-v0-4u2eanh882td1.jpeg?width=640&crop=smart&auto=webp&s=eeed9dc2ed33aa7b4ea00c7dd9fa3192689eab72" alt="Image 1">
        </div>
        <div class="image-embed">
            <img src="https://pbs.twimg.com/media/Givvlhla4AAXydn?format=jpg&name=large">
        </div>
        <div class="image-embed">
            <img src="https://pbs.twimg.com/media/Gp2Qh6dW8AECbzJ?format=jpg&name=small">
        </div>
        <div class="image-embed">
            <img src="https://pbs.twimg.com/media/GowSy0YaYAAjzx4?format=jpg&name=small">
        </div>
    </div>
    
    <script>
    document.body.addEventListener('click', function initVideoAudio() {
        document.body.removeEventListener('click', initVideoAudio);
        document.querySelectorAll('iframe').forEach(iframe => {
            if(iframe.src.includes('youtube')) {
                const newSrc = iframe.src
                    .replace('&mute=1', '')
                    .replace('mute=1&', '')
                    .replace('?mute=1', '?');
                if(newSrc !== iframe.src) {
                    iframe.src = newSrc;
                }
            }
        });
    }, {once: true});
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>