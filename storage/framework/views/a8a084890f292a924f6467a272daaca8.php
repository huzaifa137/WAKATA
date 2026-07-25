

<?php $__env->startSection('content'); ?>
    <div class="side-app">
        <div class="container-fluid mt-3">

            <style>
                .priority-pill {
                    padding: 3px 10px;
                    border-radius: 12px;
                    font-size: 0.72rem;
                    font-weight: 700;
                    letter-spacing: .3px;
                    text-transform: uppercase;
                }

                .priority-pill.normal {
                    background: #e6f7ea;
                    color: #1e7e34;
                    border: 1px solid #1e7e34;
                }

                .priority-pill.important {
                    background: #fff6e0;
                    color: #8a6100;
                    border: 1px solid #c99400;
                }

                .priority-pill.urgent {
                    background: #fdeaea;
                    color: #a71d2a;
                    border: 1px solid #a71d2a;
                }

                .read-pill {
                    padding: 3px 10px;
                    border-radius: 12px;
                    font-size: 0.72rem;
                    font-weight: 600;
                }

                .read-pill.read {
                    background: #e6f7ea;
                    color: #1e7e34;
                    border: 1px solid #1e7e34;
                }

                .read-pill.unread {
                    background: #f1f1f1;
                    color: #6c757d;
                    border: 1px solid #ccc;
                }

                .rr-table th,
                .rr-table td {
                    padding: 8px 12px;
                    border: 1px solid #e8e8e8;
                    vertical-align: middle;
                }

                .rr-table thead th {
                    background: #026837;
                    color: #fff;
                }

                .rr-table tbody tr:nth-child(even) {
                    background: #fafafa;
                }

                .message-body-box {
                    background: #f7f9f8;
                    border: 1px solid #e2e8f0;
                    border-radius: 10px;
                    padding: 18px;
                    white-space: pre-line;
                    font-size: 0.95rem;
                }
            </style>

           <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <a href="<?php echo e(route('notifications.index')); ?>" class="btn btn-back" style="
        display: inline-flex;
        align-items: center;
        gap: 12px;
        padding: 10px 26px;
        background: linear-gradient(135deg, #f80202, #fe9e9b);
        color: #fff;
        border: none;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        text-decoration: none;
        box-shadow: 0 4px 15px rgba(108, 92, 231, 0.3);
        position: relative;
        overflow: hidden;
    "
    onmouseenter="this.style.transform='translateX(-5px) scale(1.02)'; this.style.boxShadow='0 8px 25px rgba(108, 92, 231, 0.4)';"
    onmouseleave="this.style.transform=''; this.style.boxShadow='0 4px 15px rgba(108, 92, 231, 0.3)';">
        <i class="fa fa-arrow-left" style="
            transition: transform 0.3s ease;
        "></i>
        <span>Back to Notifications</span>
        <i class="fa fa-chevron-left" style="
            font-size: 0.7rem;
            opacity: 0.7;
            transition: transform 0.3s ease;
        "></i>
    </a>
</div>

            <div class="card shadow-lg border-0 mb-4">
                <div class="card-header text-white d-flex justify-content-between align-items-center flex-wrap gap-2"
                    style="background-color:#026837;">
                    <h4 class="mb-0"><i class="fa fa-envelope-open-text me-2"></i> <?php echo e($message->subject); ?></h4>
                    <span class="priority-pill <?php echo e($message->priority); ?>"><?php echo e($message->priority_label); ?></span>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <small class="text-muted d-block">Sent By</small>
                            <strong><?php echo e(optional($message->sender)->firstname); ?>

                                <?php echo e(optional($message->sender)->lastname); ?></strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Sent On</small>
                            <strong><?php echo e($message->created_at->format('d M Y, h:i A')); ?></strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Audience</small>
                            <strong><?php echo e($message->audience_label); ?></strong>
                        </div>
                    </div>

                    <div class="message-body-box"><?php echo e($message->body); ?></div>
                </div>
            </div>

            <div class="row">
                
                <div class="col-lg-6 mb-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="fa fa-school me-2"></i> Schools (<?php echo e($schoolRecipients->count()); ?>)
                            </h6>
                            <span class="text-muted small"><?php echo e($schoolRecipients->where('is_read', true)->count()); ?>

                                read</span>
                        </div>
                        <div class="card-body p-0">
                            <?php if($schoolRecipients->isEmpty()): ?>
                                <p class="text-muted small p-3 mb-0">No schools were included in this message.</p>
                            <?php else: ?>
                                <div class="table-responsive" style="max-height:420px; overflow-y:auto;">
                                    <table class="table rr-table mb-0">
                                        <thead>
                                            <tr>
                                                <th>School</th>
                                                <th>Code</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $__currentLoopData = $schoolRecipients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <?php $school = $schoolsById->get($row->recipient_id); ?>
                                                <tr>
                                                    <td class="text-start"><?php echo e($school->House ?? 'Unknown School'); ?></td>
                                                    <td><?php echo e($school->Number ?? '—'); ?></td>
                                                    <td>
                                                        <?php if($row->is_read): ?>
                                                            <span class="read-pill read"><i
                                                                    class="fa fa-check-double me-1"></i>Read</span>
                                                        <?php else: ?>
                                                            <span class="read-pill unread">Unread</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                
                <div class="col-lg-6 mb-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="fa fa-user-shield me-2"></i> System Users
                                (<?php echo e($userRecipients->count()); ?>)</h6>
                            <span class="text-muted small"><?php echo e($userRecipients->where('is_read', true)->count()); ?>

                                read</span>
                        </div>
                        <div class="card-body p-0">
                            <?php if($userRecipients->isEmpty()): ?>
                                <p class="text-muted small p-3 mb-0">No system users were included in this message.</p>
                            <?php else: ?>
                                <div class="table-responsive" style="max-height:420px; overflow-y:auto;">
                                    <table class="table rr-table mb-0">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Username</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $__currentLoopData = $userRecipients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <?php $user = $usersById->get($row->recipient_id); ?>
                                                <tr>
                                                    <td class="text-start">
                                                        <?php echo e($user ? $user->firstname . ' ' . $user->lastname : 'Unknown User'); ?>

                                                    </td>
                                                    <td><?php echo e($user->username ?? '—'); ?></td>
                                                    <td>
                                                        <?php if($row->is_read): ?>
                                                            <span class="read-pill read"><i
                                                                    class="fa fa-check-double me-1"></i>Read</span>
                                                        <?php else: ?>
                                                            <span class="read-pill unread">Unread</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
                </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts-side-bar.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/u453625278/domains/kamssamock.com/public_html/resources/views/broadcast-messages/show.blade.php ENDPATH**/ ?>