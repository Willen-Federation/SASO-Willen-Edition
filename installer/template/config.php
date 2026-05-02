<?php $this->title = 'インストール前の設定'; ?>
<?php $this->content = function($v) { ?>

<h2 class="mb-2">インストール前の設定</h2>
<p class="text-muted mb-4">SASO をご利用いただく前に、サーバ側のファイルとデータベースを準備します。各ステップを順に進めてください。</p>

<div class="alert alert-info d-flex align-items-center" role="alert">
  <i class="bi bi-info-circle-fill flex-shrink-0 me-2"></i>
  <div>以下のコードは一例です。お使いの環境に合わせて値を読み替えてください。</div>
</div>

<!-- ステップ１：データベースの作成 -->
<div class="card mb-4">
  <div class="card-header d-flex align-items-center">
    <i class="bi bi-database me-2 text-primary"></i>
    <strong>ステップ１：データベースの作成</strong>
  </div>
  <div class="card-body">
    <p>SASO が使用するデータベースを作成します。</p>
    <ol class="mb-0">
      <li>あなたがお使いのサーバのデータベースシステムに空のデータベースを作成して下さい。</li>
    </ol>
  </div>
</div>

<!-- ステップ２：「config.json」ファイルの編集 -->
<div class="card mb-4">
  <div class="card-header d-flex align-items-center">
    <i class="bi bi-file-earmark-text me-2 text-primary"></i>
    <strong>ステップ２：「config.json」ファイルの編集</strong>
  </div>
  <div class="card-body">
    <p>SASO 本体が入ったフォルダ（以下、SASO フォルダ）の直下にある「config.json」を編集します。</p>
    <ol>
      <li class="mb-3">
        <p><code>documentRoot</code> の値を SASO フォルダの置き場所（絶対パス）にして下さい。</p>
        <figure class="mb-0">
          <figcaption class="small text-muted mb-1">config.json</figcaption>
          <pre class="bg-light border rounded p-3 mb-0"><code>  "documentRoot": "/var/www/html",</code></pre>
        </figure>
      </li>
      <li class="mb-3">
        <p><code>programDir</code> の値を SASO フォルダの名称にして下さい。</p>
        <figure class="mb-2">
          <figcaption class="small text-muted mb-1">config.json</figcaption>
          <pre class="bg-light border rounded p-3 mb-0"><code>  "programDir": "saso",</code></pre>
        </figure>
        <p>あるいは、ドキュメントルート直下に SASO 本体を置く場合は空文字列にしてください。</p>
        <figure class="mb-0">
          <figcaption class="small text-muted mb-1">config.json</figcaption>
          <pre class="bg-light border rounded p-3 mb-0"><code>  "programDir": "",</code></pre>
        </figure>
      </li>
      <li>
        <p><code>database</code> 内の各項目に文字列を入力して下さい。</p>
        <p>PDO でデータベースに接続するための DSN、データベースのユーザ名、パスワードをそれぞれ該当箇所に入力してください。</p>
        <figure class="mb-0">
          <figcaption class="small text-muted mb-1">config.json</figcaption>
          <pre class="bg-light border rounded p-3 mb-0"><code>  "database": {
    "dsn": "mysql:host=localhost;dbname=saso_db;charset=utf8",
    "user": "saso_user",
    "password": "saso_sql"
  },</code></pre>
        </figure>
      </li>
    </ol>
  </div>
</div>

<!-- ステップ３：「.htaccess」ファイルの編集 -->
<div class="card mb-4">
  <div class="card-header d-flex align-items-center">
    <i class="bi bi-file-earmark-code me-2 text-primary"></i>
    <strong>ステップ３：「.htaccess」ファイルの編集</strong>
  </div>
  <div class="card-body">
    <ol class="mb-0">
      <li>
        <p>SASO フォルダ直下の隠しファイル「.htaccess」を編集します。</p>
        <p><code>RewriteBase</code> に SASO フォルダを指定してください。「/」（スラッシュ）はフォルダ名の最初と最後に必要です。</p>
        <figure class="mb-2">
          <figcaption class="small text-muted mb-1">.htaccess</figcaption>
          <pre class="bg-light border rounded p-3 mb-0"><code>RewriteBase /saso/</code></pre>
        </figure>
        <p>あるいは、ドキュメントルート直下に SASO 本体を置く場合は「/」（スラッシュ）のみ指定してください。</p>
        <figure class="mb-0">
          <figcaption class="small text-muted mb-1">.htaccess</figcaption>
          <pre class="bg-light border rounded p-3 mb-0"><code>RewriteBase /</code></pre>
        </figure>
      </li>
    </ol>
  </div>
</div>

<!-- ステップ４：管理者アカウントの作成 -->
<div class="card mb-4">
  <div class="card-header d-flex align-items-center">
    <i class="bi bi-person-plus-fill me-2 text-primary"></i>
    <strong>ステップ４：お名前とログイン ID、パスワードを入力しインストール</strong>
  </div>
  <div class="card-body">
    <p>下記の項目を入力し、「インストール」ボタンを押して下さい。</p>
    <div class="alert alert-warning d-flex align-items-center" role="alert">
      <i class="bi bi-exclamation-triangle-fill flex-shrink-0 me-2"></i>
      <div>ログイン ID とパスワードはどこかに書き留めておいて下さい。忘れると、復元できません。</div>
    </div>

    <form method="post" action="./installer/install/">
      <div class="row g-3">
        <div class="col-md-6">
          <label for="inAuthedDisplayName" class="form-label">お名前 <span class="text-danger">*</span></label>
          <input id="inAuthedDisplayName" type="text" class="form-control" name="name" maxlength="50" required>
          <div class="form-text">50字以内、日本語可</div>
        </div>
        <div class="col-md-6">
          <label for="loginId" class="form-label">ログイン ID <span class="text-danger">*</span></label>
          <input id="loginId" type="text" class="form-control" name="id" pattern="^[0-9a-zA-Z_\-]+$" maxlength="20" required>
          <div class="form-text">8〜20字、半角英数及び「-」(ハイフン)「_」(アンダースコア)</div>
        </div>
        <div class="col-md-6">
          <label for="loginPassword" class="form-label">パスワード <span class="text-danger">*</span></label>
          <input id="loginPassword" type="password" class="form-control" name="password" pattern="^[0-9a-zA-Z]+$" minlength="8" maxlength="20" required>
          <div class="form-text">8〜20字、半角英数</div>
        </div>
        <div class="col-md-6">
          <label for="loginPasswordConfirm" class="form-label">パスワード確認 <span class="text-danger">*</span></label>
          <input id="loginPasswordConfirm" type="password" class="form-control" name="password_confirm" pattern="^[0-9a-zA-Z]+$" minlength="8" maxlength="20" required>
          <div class="form-text">同じパスワードを再入力してください。</div>
        </div>
        <div class="col-12 d-flex flex-wrap gap-2">
          <button type="submit" class="btn btn-primary"><i class="bi bi-download me-1"></i>インストール</button>
        </div>
      </div>
    </form>
  </div>
</div>

<?php } ?>
