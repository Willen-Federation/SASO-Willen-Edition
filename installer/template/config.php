<?php $this->title = 'SASO インストール'; ?>
<?php $this->content = function($v) { ?>

<h2>データベース設定</h2>
<p>SASOが使用するデータベースの接続情報を入力してください。<br>
事前にサーバ上に空のデータベースを作成しておいてください。</p>

<form method="post" action="./installer/install/">
  <div class="row mb-3">
    <label for="dbHost" class="col-sm-3 col-form-label">DB ホスト</label>
    <div class="col-sm-9">
      <input id="dbHost" class="form-control" type="text" name="db_host" value="localhost" maxlength="253" required>
      <div class="form-text">例: localhost &nbsp;/&nbsp; 127.0.0.1 &nbsp;/&nbsp; db.example.com</div>
    </div>
  </div>
  <div class="row mb-3">
    <label for="dbPort" class="col-sm-3 col-form-label">DB ポート <span class="text-muted fw-normal">(省略可)</span></label>
    <div class="col-sm-9">
      <input id="dbPort" class="form-control" type="number" name="db_port" placeholder="3306" min="1" max="65535">
      <div class="form-text">省略時はデフォルトポート (3306) を使用します。</div>
    </div>
  </div>
  <div class="row mb-3">
    <label for="dbName" class="col-sm-3 col-form-label">データベース名</label>
    <div class="col-sm-9">
      <input id="dbName" class="form-control" type="text" name="db_name" maxlength="64" required>
    </div>
  </div>
  <div class="row mb-3">
    <label for="dbUser" class="col-sm-3 col-form-label">DB ユーザー名</label>
    <div class="col-sm-9">
      <input id="dbUser" class="form-control" type="text" name="db_user" maxlength="80" required>
    </div>
  </div>
  <div class="row mb-3">
    <label for="dbPassword" class="col-sm-3 col-form-label">DB パスワード</label>
    <div class="col-sm-9">
      <input id="dbPassword" class="form-control" type="password" name="db_password" maxlength="255" autocomplete="new-password">
    </div>
  </div>
  <div class="row mb-3">
    <label for="dbCharset" class="col-sm-3 col-form-label">文字コード</label>
    <div class="col-sm-9">
      <select id="dbCharset" class="form-select" name="db_charset">
        <option value="utf8mb4" selected>utf8mb4 (推奨)</option>
        <option value="utf8">utf8</option>
      </select>
    </div>
  </div>
  <div class="row mb-4">
    <div class="col-sm-9 offset-sm-3">
      <div class="form-check">
        <input id="httpsEnabled" class="form-check-input" type="checkbox" name="https_enabled" value="1">
        <label class="form-check-label" for="httpsEnabled">HTTPS を使用する (https:// で運用する場合はチェック)</label>
      </div>
    </div>
  </div>

  <hr class="my-4">

  <h2>管理者アカウント</h2>
  <p>ログインに使用する管理者アカウントを作成します。<br>
  ログインIDとパスワードはメモしておいてください。忘れると復元できません。</p>

  <div class="row mb-3">
    <label for="inAuthedDisplayName" class="col-sm-3 col-form-label">お名前</label>
    <div class="col-sm-9">
      <input id="inAuthedDisplayName" class="form-control" type="text" name="name" maxlength="50" required>
      <div class="form-text">50字以内、日本語可</div>
    </div>
  </div>
  <div class="row mb-3">
    <label for="loginId" class="col-sm-3 col-form-label">ログインID</label>
    <div class="col-sm-9">
      <input id="loginId" class="form-control" type="text" name="id" pattern="^[0-9a-zA-Z_\-]+$" minlength="8" maxlength="20" required>
      <div class="form-text">8〜20字、半角英数および「-」(ハイフン)「_」(アンダースコア)</div>
    </div>
  </div>
  <div class="row mb-3">
    <label for="loginPassword" class="col-sm-3 col-form-label">パスワード</label>
    <div class="col-sm-9">
      <input id="loginPassword" class="form-control" type="password" name="password" pattern="^[0-9a-zA-Z]+$" minlength="8" maxlength="20" required autocomplete="new-password">
      <div class="form-text">8〜20字、半角英数</div>
    </div>
  </div>
  <div class="row mb-4">
    <label for="loginPasswordConfirm" class="col-sm-3 col-form-label">パスワード確認</label>
    <div class="col-sm-9">
      <input id="loginPasswordConfirm" class="form-control" type="password" name="password_confirm" pattern="^[0-9a-zA-Z]+$" minlength="8" maxlength="20" required autocomplete="new-password">
    </div>
  </div>

  <div class="mb-5">
    <button class="btn btn-primary btn-lg" type="submit">インストール</button>
  </div>
</form>

<?php } ?>
