

<?php $__env->startSection('content'); ?>
    <div class="side-app">
        <div class="container-fluid mt-3">
            <div class="card shadow-lg border-0">
                <div class="card-header text-white d-flex justify-content-between align-items-center flex-wrap gap-2"
                    style="background-color:#026837;">
                    <h4 class="mb-0"><i class="fa fa-inbox me-2"></i> My Inbox</h4>
                    <a href="<?php echo e(route('notifications.index')); ?>" class="btn btn-sm btn-outline-light px-3 py-2 rounded-pill">
                        <span style="color:#FFF;"><i class="fa fa-bullhorn me-2"></i> Notifications Hub</span>
                    </a>
                </div>
                <div class="card-body">
                    <p class="text-muted small">Messages sent directly to your system-user account. Click a message to
                        read it in full — it's marked as read automatically.</p>

                    <?php echo $__env->make('broadcast-messages.partials.inbox-list', [
                        'recipientRows' => $recipientRows,
                        'markReadUrlBase' => '/notifications/inbox',
                    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </div>
            </div>
        </div>
    </div>
               </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts-side-bar.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/u453625278/domains/kamssamock.com/public_html/resources/views/broadcast-messages/inbox.blade.php ENDPATH**/ ?>