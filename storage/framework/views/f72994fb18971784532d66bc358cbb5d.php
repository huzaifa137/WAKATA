
<style>
    .inbox-item {
        border: 1px solid #e2e8f0;
        border-left: 4px solid #ccc;
        border-radius: 8px;
        margin-bottom: 10px;
        overflow: hidden;
        transition: box-shadow .15s ease;
    }
    .inbox-item.unread { border-left-color: #026837; background: #f7fbf8; }
    .inbox-item:hover { box-shadow: 0 2px 10px rgba(0,0,0,.06); }

    .inbox-item-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        padding: 12px 16px;
        cursor: pointer;
        flex-wrap: wrap;
    }

    .inbox-item-subject { font-size: 0.95rem; }
    .inbox-item.unread .inbox-item-subject { font-weight: 700; }

    .inbox-item-meta { font-size: 0.78rem; color: #6c757d; }

    .inbox-item-body {
        display: none;
        padding: 0 16px 16px 16px;
        white-space: pre-line;
        font-size: 0.9rem;
        border-top: 1px dashed #e2e8f0;
        padding-top: 12px;
    }
    .inbox-item-body.show { display: block; }

    .unread-dot {
        width: 9px; height: 9px; border-radius: 50%;
        background: #026837; display: inline-block; margin-right: 8px;
        flex-shrink: 0;
    }

    .priority-pill {
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: .3px;
        text-transform: uppercase;
    }
    .priority-pill.normal    { background:#e6f7ea; color:#1e7e34; border:1px solid #1e7e34; }
    .priority-pill.important { background:#fff6e0; color:#8a6100; border:1px solid #c99400; }
    .priority-pill.urgent    { background:#fdeaea; color:#a71d2a; border:1px solid #a71d2a; }
</style>

<?php if($recipientRows->isEmpty()): ?>
    <div class="text-center text-muted py-5">
        <i class="fa fa-inbox fa-2x mb-2 d-block"></i>
        No messages in your inbox yet.
    </div>
<?php else: ?>
    <?php $__currentLoopData = $recipientRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php $message = $row->message; ?>
        <div class="inbox-item <?php echo e($row->is_read ? '' : 'unread'); ?>" id="inbox-item-<?php echo e($row->id); ?>">
            <div class="inbox-item-head" onclick="ibToggle(<?php echo e($row->id); ?>)">
                <div class="d-flex align-items-center" style="min-width:0;">
                    <?php if(!$row->is_read): ?>
                        <span class="unread-dot"></span>
                    <?php endif; ?>
                    <div style="min-width:0;">
                        <div class="inbox-item-subject text-truncate"><?php echo e($message->subject); ?></div>
                        <div class="inbox-item-meta">
                            From <?php echo e(optional($message->sender)->firstname); ?> <?php echo e(optional($message->sender)->lastname); ?>

                            &middot; <?php echo e($message->created_at->diffForHumans()); ?>

                        </div>
                    </div>
                </div>
                <span class="priority-pill <?php echo e($message->priority); ?>"><?php echo e($message->priority_label); ?></span>
            </div>
            <div class="inbox-item-body" id="inbox-item-body-<?php echo e($row->id); ?>"><?php echo e($message->body); ?></div>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php endif; ?>

<script>
    function ibToggle(rowId) {
        const body = document.getElementById('inbox-item-body-' + rowId);
        const item = document.getElementById('inbox-item-' + rowId);
        const willShow = !body.classList.contains('show');

        document.querySelectorAll('.inbox-item-body.show').forEach(el => el.classList.remove('show'));
        if (willShow) body.classList.add('show');

        if (willShow && item.classList.contains('unread')) {
            fetch('<?php echo e($markReadUrlBase); ?>/' + rowId + '/read', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>',
                    'Accept': 'application/json',
                },
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        item.classList.remove('unread');
                        const dot = item.querySelector('.unread-dot');
                        if (dot) dot.remove();
                    }
                })
                .catch(() => {});
        }
    }
</script>
<?php /**PATH C:\Users\USER\Desktop\KAMSSA\resources\views/broadcast-messages/partials/inbox-list.blade.php ENDPATH**/ ?>