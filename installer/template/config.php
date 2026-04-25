<?php $this->title = 'インストール前の設定'; ?>
<?php $this->content = function($v) { ?>

<p>※以下のコードは一例です。</p>
<h2>ステップ１：データベースの作成</h2>
<p>SASOが使用するデータベースを作成します。<p>
<ol>
<li>あなたがお使いのサーバのデータベースシステムに空のデータベースを作成して下さい。</li>
</ol>

<h2>ステップ２：「config.json」ファイルの編集</h2>
<p>SASO本体が入ったフォルダ（以下、SASOフォルダ）の直下にある「config.json」を編集します。<p>
<ol>
  <li>
    <p>documentRootの値をSASOフォルダの置き場所（絶対パス）にして下さい。</p>
    <div class="tab">config.json</div>
    <pre class="tabbed"><code>  "documentRoot": "/var/www/html",</code></pre>
  </li>
  <li>
    <p>programDirの値をSASOフォルダの名称にして下さい。</p>
    <div class="tab">config.json</div>
    <pre class="tabbed"><code>  "programDir": "saso",</code></pre>
    <p>あるいは、ドキュメントルート直下にSASO本体を置く場合は空文字列にしてください。</p>
    <div class="tab">config.json</div>
    <pre class="tabbed"><code>  "programDir": "",</code></pre>
  </li>
  <li>
    <p>database内の各項目に文字列を入力して下さい。</p>
    <p>PDOでデータベースに接続するためのDSN、データベースのユーザ名、パスワードをそれぞれ該当箇所に入力してください。</p>
    <div class="tab">config.json</div>
    <pre class="tabbed"><code>  "database": {
    "dsn": "mysql:host=localhost;dbname=saso_db;charset=utf8",
    "user": "saso_user",
    "password": "saso_sql"
  },</code></pre>
  </li>
</ol>

<h2>ステップ３：「.htaccess」ファイルの編集</h2>
<ol>
  <li>
    <p>SASOフォルダ直下の隠しファイル「.htaccess」を編集します。</p>
    <p>RewriteBaseにSASOフォルダを指定してください。「/」（スラッシュ）はフォルダ名の最初と最後に必要です。</p>
    <div class="tab">.htaccess</div>
    <pre class="tabbed"><code>RewriteBase /saso/</code></pre>
    <p>あるいは、ドキュメントルート直下にSASO本体を置く場合は「/」（スラッシュ）のみ指定してください。</p>
    <div class="tab">.htaccess</div>
    <pre class="tabbed"><code>RewriteBase /</code></pre>
  </li>
</ol>

<h2>ステップ４：お名前とログインID、パスワードを入力しインストール</h2>
<p>下記の項目を入力し、インストールボタンを押して下さい。
<br>ログインIDとパスワードはどこかに書き留めておいて下さい。忘れると、復元できません。<p>

<form method="post" action="./installer/install/">
  <div class="row mb-3">
    <label for="inAuthedDisplayName" class="col-sm-2 col-form-label">お名前</label>
    <div class="col-sm-10">
      <input id="inAuthedDisplayName" class="form-control" type="text" name="name" maxlength="50" required>
      50字以内、日本語可
    </div>
  </div>
  <div class="row mb-3">
    <label for="loginId" class="col-sm-2 col-form-label">ログインID</label>
    <div class="col-sm-10">
    <input id="loginId" class="form-control" type="text" name="id" pattern="^[0-9a-zA-Z_\-]+$" maxlength="20" required>
    8-20字、半角英数及び「-」(ハイフン)「_」(アンダースコア)
    </div>
  </div>
  <div class="row mb-3">
    <label for="loginPassword" class="col-sm-2 col-form-label">パスワード</label>
    <div class="col-sm-10">
    <input id="loginPassword" class="form-control" type="password" name="password" pattern="^[0-9a-zA-Z]+$" minlength="8" maxlength="20" required>
    8-20字、半角英数
    </div>
  </div>
  <div class="row mb-3">
    <label for="loginPasswordConfirm" class="col-sm-2 col-form-label">パスワード確認</label>
    <div class="col-sm-10">
    <input id="loginPasswordConfirm" class="form-control" type="password" name="password_confirm" pattern="^[0-9a-zA-Z]+$" minlength="8" maxlength="20" required>
    </div>
  </div>
  <div class="mb-5">
  <button class="btn btn-primary" type="submit">インストール</button>
  </div>
</form>

<?php } ?>
