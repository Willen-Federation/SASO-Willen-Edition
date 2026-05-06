<?php $this->title = 'インストール前の設定'; ?>
<?php $this->content = function($v) { ?>

<h2>インストール前の設定</h2>
<p>SASO をご利用いただく前に、サーバ側のファイルとデータベースを準備します。各ステップを順に進めてください。</p>

<h3>ステップ1：データベースの作成</h3>
<p>SASO が使用するデータベースを作成します。</p>
<ol>
  <li>あなたがお使いのサーバのデータベースシステムに空のデータベースを作成して下さい。</li>
</ol>

<h3>ステップ2：「config.json」ファイルの編集</h3>
<p>SASO 本体が入ったフォルダ（以下、SASO フォルダ）の直下にある「config.json」を編集します。</p>
<ol>
  <li>
    <p><code>documentRoot</code> の値を SASO フォルダの置き場所（絶対パス）にして下さい。</p>
    <div class="tab">
      <pre class="tabbed">  "documentRoot": "/var/www/html",</pre>
    </div>
  </li>
  <li>
    <p><code>programDir</code> の値を SASO フォルダの名称にして下さい。</p>
    <div class="tab">
      <pre class="tabbed">  "programDir": "saso",</pre>
    </div>
    <p>あるいは、ドキュメントルート直下に SASO 本体を置く場合は空文字列にしてください。</p>
    <div class="tab">
      <pre class="tabbed">  "programDir": "",</pre>
    </div>
  </li>
  <li>
    <p><code>database</code> 内の各項目に文字列を入力して下さい。</p>
    <p>PDO でデータベースに接続するための DSN、データベースのユーザ名、パスワードをそれぞれ該当箇所に入力してください。</p>
    <div class="tab">
      <pre class="tabbed">  "database": {
    "dsn": "mysql:host=localhost;dbname=saso_db;charset=utf8",
    "user": "saso_user",
    "password": "saso_sql"
  },</pre>
    </div>
  </li>
</ol>

<h3>ステップ3：「.htaccess」ファイルの編集</h3>
<ol>
  <li>
    <p>SASO フォルダ直下の隠しファイル「.htaccess」を編集します。</p>
    <p><code>RewriteBase</code> に SASO フォルダを指定してください。「/」（スラッシュ）はフォルダ名の最初と最後に必要です。</p>
    <div class="tab">
      <pre class="tabbed">RewriteBase /saso/</pre>
    </div>
    <p>あるいは、ドキュメントルート直下に SASO 本体を置く場合は「/」（スラッシュ）のみ指定してください。</p>
    <div class="tab">
      <pre class="tabbed">RewriteBase /</pre>
    </div>
  </li>
</ol>

<h3>ステップ4：管理者アカウントの作成</h3>
<p>下記の項目を入力し、「インストール」ボタンを押して下さい。</p>
<p style="color: orange; background-color: #ffe0e0; padding: 10px; border-left: 4px solid orange;">
  ログイン ID とパスワードはどこかに書き留めておいて下さい。忘れると、復元できません。
</p>

<form method="post" action="./installer/install/">
  <p>お名前：<input type="text" name="name" maxlength="50" required> （50字以内、日本語可）</p>
  <p>ログイン ID：<input type="text" name="id" pattern="^[0-9a-zA-Z_\-]+$" maxlength="20" required> （8〜20字、半角英数及び「-」(ハイフン)「_」(アンダースコア)）</p>
  <p>パスワード：<input type="password" name="password" pattern="^[0-9a-zA-Z]+$" minlength="8" maxlength="20" required> （8〜20字、半角英数）</p>
  <p>パスワード確認：<input type="password" name="password_confirm" pattern="^[0-9a-zA-Z]+$" minlength="8" maxlength="20" required> （同じパスワードを再入力してください）</p>
  <p><input type="submit" value="インストール"></p>
</form>

<?php } ?>
