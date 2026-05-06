<?php
/** @var \saso\mypage\EditProfileView $v */
?>
<?php $v->title = __('Edit Profile'); ?>
<?php $v->content = function ($v) { ?>
<div class="container mt-5">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h2><?php echo htmlspecialchars(__('Edit Profile')); ?></h2>
                </div>
                <div class="card-body">
                    <?php if ($v->member): ?>
                    <form method="POST" action="./mypage/editProfile/">
                        <div class="mb-3">
                            <label for="display_name" class="form-label">
                                <?php echo htmlspecialchars(__('Display Name')); ?>
                            </label>
                            <input
                                type="text"
                                class="form-control"
                                id="display_name"
                                name="display_name"
                                maxlength="100"
                                value="<?php echo htmlspecialchars($v->member->displayName ?: ''); ?>"
                                placeholder="<?php echo htmlspecialchars($v->member->name); ?>">
                            <small class="form-text text-muted">
                                <?php echo htmlspecialchars(__('Your name displayed in the application')); ?>
                            </small>
                        </div>

                        <div class="mb-3">
                            <label for="bio" class="form-label">
                                <?php echo htmlspecialchars(__('Bio')); ?>
                            </label>
                            <textarea
                                class="form-control"
                                id="bio"
                                name="bio"
                                rows="4"
                                maxlength="500"><?php echo htmlspecialchars($v->member->bio ?: ''); ?></textarea>
                            <small class="form-text text-muted">
                                <?php echo htmlspecialchars(__('About yourself (max 500 characters)')); ?>
                            </small>
                        </div>

                        <div class="mb-3">
                            <label for="avatar_url" class="form-label">
                                <?php echo htmlspecialchars(__('Avatar URL')); ?>
                            </label>
                            <input
                                type="url"
                                class="form-control"
                                id="avatar_url"
                                name="avatar_url"
                                maxlength="500"
                                value="<?php echo htmlspecialchars($v->member->avatarUrl ?: ''); ?>"
                                placeholder="https://example.com/avatar.jpg">
                            <small class="form-text text-muted">
                                <?php echo htmlspecialchars(__('URL to your avatar image (JPG, PNG, WebP)')); ?>
                            </small>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <?php echo htmlspecialchars(__('Save')); ?>
                            </button>
                            <a href="./mypage/start/" class="btn btn-secondary">
                                <?php echo htmlspecialchars(__('Cancel')); ?>
                            </a>
                        </div>
                    </form>

                    <?php else: ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars(__('Member data not found')); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php }; ?>
