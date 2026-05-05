<?php
/** @var \saso\mypage\MyPageView $v */
?>
<?php $v->title = __('My Page'); ?>
<?php $v->content = function ($v) { ?>
<div class="container mt-5">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h2><?php echo htmlspecialchars(__('My Profile')); ?></h2>
                </div>
                <div class="card-body">
                    <?php if ($v->member): ?>
                    <div class="profile-section mb-4">
                        <div class="row">
                            <div class="col-md-3 text-center">
                                <div class="avatar-container">
                                    <?php if ($v->member->avatarUrl): ?>
                                    <img src="<?php echo htmlspecialchars($v->member->avatarUrl); ?>"
                                         alt="<?php echo htmlspecialchars($v->member->name); ?>"
                                         class="rounded-circle" width="120" height="120">
                                    <?php else: ?>
                                    <i class="bi bi-person-circle" style="font-size: 5rem;"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-9">
                                <dl class="row">
                                    <dt class="col-sm-3"><?php echo htmlspecialchars(__('User ID')); ?></dt>
                                    <dd class="col-sm-9"><?php echo htmlspecialchars($v->member->id); ?></dd>

                                    <dt class="col-sm-3"><?php echo htmlspecialchars(__('Display Name')); ?></dt>
                                    <dd class="col-sm-9">
                                        <?php echo htmlspecialchars($v->member->displayName ?: $v->member->name); ?>
                                    </dd>

                                    <dt class="col-sm-3"><?php echo htmlspecialchars(__('Role')); ?></dt>
                                    <dd class="col-sm-9">
                                        <span class="badge bg-primary">
                                            <?php echo htmlspecialchars($v->member->role); ?>
                                        </span>
                                    </dd>

                                    <?php if ($v->member->bio): ?>
                                    <dt class="col-sm-3"><?php echo htmlspecialchars(__('Bio')); ?></dt>
                                    <dd class="col-sm-9"><?php echo htmlspecialchars($v->member->bio); ?></dd>
                                    <?php endif; ?>

                                    <?php if ($v->member->updatedAt): ?>
                                    <dt class="col-sm-3"><?php echo htmlspecialchars(__('Updated')); ?></dt>
                                    <dd class="col-sm-9"><?php echo $v->member->updatedAt->format('Y-m-d H:i'); ?></dd>
                                    <?php endif; ?>
                                </dl>

                                <div class="mt-3">
                                    <a href="/mypage/editProfile/" class="btn btn-primary btn-sm">
                                        <?php echo htmlspecialchars(__('Edit Profile')); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="auth-section">
                        <h3><?php echo htmlspecialchars(__('Authentication Methods')); ?></h3>
                        <p class="text-muted"><?php echo htmlspecialchars(__('Manage your login methods')); ?></p>

                        <div class="alert alert-info">
                            <strong><?php echo htmlspecialchars(__('Coming Soon')); ?></strong>
                            <?php echo htmlspecialchars(__('Authentication method management will be available in the next release.')); ?>
                        </div>

                        <div class="mt-3">
                            <h5><?php echo htmlspecialchars(__('Local Authentication')); ?></h5>
                            <p><?php echo htmlspecialchars(__('Manage your password')); ?></p>
                            <a href="/auth/password/" class="btn btn-outline-primary btn-sm">
                                <?php echo htmlspecialchars(__('Change Password')); ?>
                            </a>
                        </div>
                    </div>

                    <?php else: ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars(__('Member data not found')); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5><?php echo htmlspecialchars(__('Quick Links')); ?></h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="/mypage/editProfile/" class="list-group-item list-group-item-action">
                        <i class="bi bi-pencil"></i> <?php echo htmlspecialchars(__('Edit Profile')); ?>
                    </a>
                    <a href="/auth/password/" class="list-group-item list-group-item-action">
                        <i class="bi bi-key"></i> <?php echo htmlspecialchars(__('Change Password')); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php }; ?>
