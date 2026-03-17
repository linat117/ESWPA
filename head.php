<head>
    <!-- ========== Meta Tags ========== -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Poora - Fundraising & Charity Template">

    <!-- ========== Page Title ========== -->
    <title>Ethiopian Social Work Professional Association</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <!-- ========== Favicon Icon ========== -->
    <link rel="shortcut icon" href="assets/img/content/favicon.png" type="image/x-icon">

    <!-- ========== Start Stylesheet ========== -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" />
    <link href="assets/css/font-awesome.min.css" rel="stylesheet" />
    <link href="assets/css/themify-icons.css" rel="stylesheet" />
    <link href="assets/css/flaticon-set.css" rel="stylesheet" />
    <link href="assets/css/magnific-popup.css" rel="stylesheet" />
    <link href="assets/css/owl.carousel.min.css" rel="stylesheet" />
    <link href="assets/css/owl.theme.default.min.css" rel="stylesheet" />
    <link href="assets/css/animate.css" rel="stylesheet" />
    <link href="assets/css/bootsnav.css" rel="stylesheet" />
    <link href="style.css" rel="stylesheet">
    <link href="assets/css/responsive.css" rel="stylesheet" />
    <link href="assets/css/home.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <!-- Temporary override: kill any accidental global blur/overlay -->
    <style>
        /* Kill overlays on public site */
        #preloader,
        #status,
        #ds-loading-overlay,
        .ds-loading-overlay,
        .ds-loading-overlay-dark,
        .modal-backdrop,
        .ui-widget-overlay,
        .subscription-popup-overlay {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
        }

        /* Remove any accidental global blur/dimming */
        html, body, body * {
            filter: none !important;
            backdrop-filter: none !important;
        }
    </style>

    <!-- Safety JS: remove any unexpected full-screen overlays with huge z-index -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            setTimeout(function () {
                var elements = document.querySelectorAll('body *');
                elements.forEach(function (el) {
                    try {
                        var style = window.getComputedStyle(el);
                        var z = parseInt(style.zIndex || '0', 10);
                        if ((style.position === 'fixed' || style.position === 'absolute') &&
                            z >= 9999 &&
                            (style.width === '100%' || style.height === '100%')) {
                            el.style.display = 'none';
                            el.style.visibility = 'hidden';
                            el.style.pointerEvents = 'none';
                            el.style.opacity = 0;
                        }
                    } catch (e) {
                        // ignore
                    }
                });
            }, 500);
        });
    </script>

    <!-- ========== End Stylesheet ========== -->

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="assets/js/html5/html5shiv.min.js"></script>
      <script src="assets/js/html5/respond.min.js"></script>
    <![endif]-->



</head>