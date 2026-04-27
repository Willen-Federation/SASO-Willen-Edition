<?php $this->content = function($v) { ?>
<!DOCTYPE html>
<html lang="ja" dir="ltr">
<head>
<base href="<?php echo $v->baseUrl; ?>">
<meta charset="utf-8">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link href="./css/style.css" rel="stylesheet">
<link rel="shortcut icon" href="./favicon.ico" type="image/vnd.microsoft.icon">
<link rel="icon" href="./favicon.ico" type="image/vnd.microsoft.icon">
<title>在庫管理システム「SASO<?php echo $v->version; ?>」 - <?php echo $v->insideView->getTitle(); ?></title>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <a class="navbar-brand" href="./">在庫管理システム『SASO<?php echo $v->version; ?>』</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <?php if($v->authed) { ?>
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link" aria-current="page" href="./">ホーム</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="./item/add/">商品登録</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="./shelf/start/">棚番作成</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="./label/features/">商品ラベル印刷</a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownArchive" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            アーカイブ
          </a>
          <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
            <li><a class="dropdown-item" href="./archive/list/">アーカイブ一覧</a></li>
            <li><a class="dropdown-item" href="./item/archivingAll/">一括アーカイブ</a></li>
          </ul>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownAdmin" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            管理
          </a>
          <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
            <li><a class="dropdown-item" href="./label/start/">ラベル寸法管理</a></li>
            <li><a class="dropdown-item" href="./category/start/">分類管理</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="./start/password/">パスワード変更</a></li>
          </ul>
        </li>
      </ul>
      <?php if(false) { ?>
      <!--<form class="d-flex">
        <input class="form-control me-2" type="search" placeholder="Search" aria-label="Search">
        <button class="btn btn-outline-success" type="submit">Search</button>
      </form>-->
      <?php } ?>
    </div>
    <?php } ?>
  </div>
</nav>

<div class="container-fluid">
  <div class="row">
      <h1 class="col-8"><?php echo $v->insideView->getTitle(); ?></h1>
      <?php if($v->authed) { ?>
      <div class="col-4">
      <p class="text-success"><?php echo $_SESSION['userName'] . '様ログイン中。' ?>
        <a class="btn btn-secondary" href="./start/logout/">ログアウト</a></p>
      </div>
      <?php } ?>
  </div>
  <div class="row">
    <div class="col-12">
      <?php $v->insideView->getContent()($v->insideView); ?>
    </div>
  </div>
</div>
<script type="module">import "./js/main.js";</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
</body>
</html>

<?php } ?>
