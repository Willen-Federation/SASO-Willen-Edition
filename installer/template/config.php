<?php $this->title = 'インストール前の設定'; ?>
<?php $this->content = function($v) { ?>

<div class="card mb-3">
  <div class="card-body">
    <h2 class="card-title">インストール前の設定</h2>
    <p class="text-secondary">SASO をご利用いただく前に、サーバ側のファイルとデータベースを準備します。各ステップを順に進めてください。</p>

    <h3 class="mt-4">ステップ1：データベースの作成</h3>
    <p>SASO が使用するデータベースを作成します。</p>
    <ol>
      <li>あなたがお使いのサーバのデータベースシステムに空のデータベースを作成して下さい。</li>
    </ol>

    <h3 class="mt-4">ステップ2：<code>config.json</code> ファイルの編集</h3>
    <p>SASO 本体が入ったフォルダ（以下、SASO フォルダ）の直下にある <code>config.json</code> を編集します。</p>
    <ol>
      <li>
        <p><code>documentRoot</code> の値を SASO フォルダの置き場所（絶対パス）にして下さい。</p>
        <pre class="bg-light border rounded p-2"><code>"documentRoot": "/var/www/html",</code></pre>
      </li>
      <li>
        <p><code>programDir</code> の値を SASO フォルダの名称にして下さい。</p>
        <pre class="bg-light border rounded p-2"><code>"programDir": "saso",</code></pre>
        <p>あるいは、ドキュメントルート直下に SASO 本体を置く場合は空文字列にしてください。</p>
        <pre class="bg-light border rounded p-2"><code>"programDir": "",</code></pre>
      </li>
      <li>
        <p>データベース接続情報は <code>.env</code> ファイル（SASO フォルダ直下、無ければ <code>.env.example</code> をコピーして作成）に設定してください。</p>
        <p>PDO でデータベースに接続するための DSN、ユーザ名、パスワードを <code>DB_DSN</code> / <code>DB_USER</code> / <code>DB_PASSWORD</code> として記述します。</p>
        <pre class="bg-light border rounded p-2"><code>DB_DSN=mysql:host=localhost;dbname=saso_db;charset=utf8mb4
DB_USER=saso_user
DB_PASSWORD=&lt;your password&gt;</code></pre>
        <p><code>config.json</code> の <code>database</code> セクションは空のままで構いません。<code>.env</code> の値が優先されます。</p>
      </li>
    </ol>

    <h3 class="mt-4">ステップ3：<code>.htaccess</code> ファイルの編集</h3>
    <ol>
      <li>
        <p>SASO フォルダ直下の隠しファイル <code>.htaccess</code> を編集します。</p>
        <p><code>RewriteBase</code> に SASO フォルダを指定してください。「/」（スラッシュ）はフォルダ名の最初と最後に必要です。</p>
        <pre class="bg-light border rounded p-2"><code>RewriteBase /saso/</code></pre>
        <p>あるいは、ドキュメントルート直下に SASO 本体を置く場合は「/」（スラッシュ）のみ指定してください。</p>
        <pre class="bg-light border rounded p-2"><code>RewriteBase /</code></pre>
      </li>
    </ol>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h3 class="card-title">ステップ4：管理者アカウントの作成</h3>
  </div>
  <div class="card-body">
    <p>下記の項目を入力し、「インストール」ボタンを押して下さい。</p>
    <div class="alert alert-warning" role="note">
      <i class="bi bi-exclamation-triangle me-2"></i>ログイン ID とパスワードはどこかに書き留めておいて下さい。忘れると、復元できません。
    </div>

    <form method="post" action="./installer/install/">
      <div class="mb-3">
        <label for="install-name" class="form-label">お名前 <span class="text-danger">*</span></label>
        <input type="text" id="install-name" name="name" class="form-control" maxlength="50" required>
        <div class="form-hint">50字以内、日本語可</div>
      </div>
      <div class="mb-3">
        <label for="install-id" class="form-label">ログイン ID <span class="text-danger">*</span></label>
        <input type="text" id="install-id" name="id" class="form-control"
               pattern="^[0-9a-zA-Z_\-]+$" maxlength="20" required>
        <div class="form-hint">8〜20字、半角英数及び「-」(ハイフン) と「_」(アンダースコア)</div>
      </div>
      <div class="mb-3">
        <label for="install-password" class="form-label">パスワード <span class="text-danger">*</span></label>
        <input type="password" id="install-password" name="password" class="form-control"
               pattern="^[0-9a-zA-Z]+$" minlength="8" maxlength="20" required autocomplete="new-password">
        <div class="form-hint">8〜20字、半角英数</div>
      </div>
      <div class="mb-3">
        <label for="install-password-confirm" class="form-label">パスワード確認 <span class="text-danger">*</span></label>
        <input type="password" id="install-password-confirm" name="password_confirm" class="form-control"
               pattern="^[0-9a-zA-Z]+$" minlength="8" maxlength="20" required autocomplete="new-password">
        <div class="form-hint">同じパスワードを再入力してください</div>
      </div>
      <button type="submit" class="btn btn-primary">
        <i class="bi bi-server me-1"></i>インストール
      </button>
    </form>
  </div>
</div>

<?php } ?>
