# 引き継ぎドキュメント

**生成日時**: 2026-05-05 20:30 JST
**プロジェクト**: マイページ実装と認証拡張（saso.sksl.jp）
**ステータス**: Sprint 1 完了、Sprint 2準備中

---

## ✅ 完了済みの作業

### Sprint 1: マイページ基盤構築（全9項目完了）

1. **Member エンティティ拡張**
   - 新プロパティ: avatarUrl, displayName, bio, updatedAt
   - バリデーション制約メソッド 3 個追加
   - ファイル: entity/Member.php

2. **データベーススキーマ**
   - マイグレーション: migrations/M4/20260505000000_add_profile_fields_to_member.php
   - 4カラム追加: avatar_url, display_name, bio, updated_at

3. **Repository層**
   - repository/member/FindOne.php - プロフィール情報全取得

4. **マイページ表示** (/mypage/start/)
   - DIContainer, Usecase, Presenter, View 実装
   - アバター表示、プロフィール表示、クイックリンク

5. **プロフィール編集** (/mypage/editProfile/)
   - GET: フォーム表示、POST: 保存処理
   - バリデーション + DB UPDATE

6. **ルーティング統合**
   - flow.json に "mypage" セクション追加

7. **メニュー統合**
   - start/template/menu.php に「マイページ」リンク追加

8. **テンプレート実装**
   - mypage.php, edit-profile.php, error.php

9. **Git コミット**
   - ID: f059deb - 28ファイル変更

---

## 🎯 最終ゴール

1. **パスワード変更をマイページに移転** → ✅ 基盤完成 / ⏳ リンク統合
2. **マイページ作成** → ✅ 完成
3. **Auth0連携と認証方法管理** → ✅ 設計完了 / ⏳ 実装予定

---

## 📋 残タスク（優先順）

### Sprint 2（高優先度）

- [ ] AvatarHelper ユーティリティ実装
  - 外部URL表示 + デフォルトアイコン fallback
  - ファイル: util/AvatarHelper.php

- [ ] テンプレート修正（マイページ）
  - 「認証方法」セクション追加（リンク済みプロバイダー表示）
  - パスワード変更ボタン統合

- [ ] AuthMethodsUsecase 実装
  - member_external_identity から認証リンク取得
  - auth_provider で詳細情報取得

- [ ] ブラウザテスト
  - マイグレーション実行
  - /mypage/start/ にアクセス、動作確認

### Sprint 3（中優先度）

- [ ] LinkProviderDIContainer - 認証リンク追加フロー
- [ ] Callback エンドポイント - /mypage/linkCallback/
- [ ] UnlinkProviderUsecase - 認証リンク削除
- [ ] WebAuthn テーブル作成
- [ ] WebAuthnProvider 実装

---

## 📁 作成ファイル（24個）

| ファイル | 役割 | 状態 |
|---------|------|------|
| entity/Member.php | 属性拡張 | ✅ |
| repository/member/FindOne.php | 取得 | ✅ |
| mypage/MyPage*.php | 表示機能 | ✅ |
| mypage/EditProfile*.php | 編集機能 | ✅ |
| mypage/template/* | テンプレート | ✅ |
| flow.json | ルーティング | ✅ |
| start/template/menu.php | メニュー | ✅ |

---

## ⚠️ 重要な注意事項

1. **マイグレーション未実行** - Phinx で実行必須
2. **アバター**: 外部URL指定のみ（ローカルアップロード不可）
3. **セッション認証**: /mypage/* は $_SESSION['id'] チェック
4. **デフォルトアイコン**: Bootstrap Icons の bi-person-circle

---

## 💡 推奨アプローチ

- Sprint 2: AvatarHelper → テンプレート修正 → ブラウザテスト
- Sprint 3: LinkProvider → WebAuthn（複雑、最後）
- コードスタイル: 既存パターン厳密準拠（DIContainer, Usecase, Presenter, View）

---

## 🔧 環境・依存関係

- **PHP 8.1+** (Argon2id)
- **MySQL 5.7+**
- **Composer**: phinx, openid-connect-php, php-saml
- **追加**: web-auth/webauthn-lib (Sprint 3)
- **フロント**: Bootstrap 5+, Bootstrap Icons

---

## 📝 次のセッションコマンド

```
「前回の続きをお願いします。PLAN.md と HANDOFF.md を読んで、
Sprint 2 から実装を進めてください。」
```

---

