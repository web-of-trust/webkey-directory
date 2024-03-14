<!doctype html>
<html lang="en" dir="ltr" class="">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/bootstrap.min.css" type="text/css"/>
    <title><?php echo $title; ?></title>
</head>
<body lang="en">
    <div class="container py-3 my-5">
        <div class="card">
            <div class="card-header text-center">
                <p class="h3"><a class="text-dark" href="<?php echo $homeUrl; ?>"><?php echo $title; ?></a></p>
            </div>
            <div class="card-body text-center">
                <?php if ($keyFound): ?>
                <p>An entry was found for <span class="email"><?php echo $search; ?></span></p>
                <p>
                    <a href="<?php echo $keyUrl; ?>"><?php echo $search; ?></a>
                </p>
                <?php else: ?>
                <p><strong>Error</strong>: No key found for <span class="email"><?php echo $search; ?></span></p>
                <form action="<?php echo $searchUrl; ?>" method="GET">
                    <div class="row height d-flex justify-content-center align-items-center">
                        <div class="col-md-8">
                            <div class="input-group">
                                <i class="fa fa-search"></i>
                                <input type="text" class="form-control" name="search" placeholder="Search by Email Address / Key ID / Fingerprint">
                                <button class="btn btn-primary">
                                    <img src="<?php echo $basePath; ?>assets/search.svg" style="width: 1em; padding-bottom: 4px;"> Search
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
                <?php endif;?>
            </div>
        </div>
    </div>
    <footer class="footer fixed-bottom mt-auto py-3 bg-light">
      <div class="container text-center">
            Powered by <a href="https://github.com/web-of-trust" class="text-dark">Web Of Trust</a>
      </div>
    </footer>
</body>
</html>
