
<div class="assignment-picker" id="picker-<?php echo e($prefix); ?>">
    <input type="text" class="form-control form-control-sm mb-2 assignment-search" placeholder="Search subjects..."
        oninput="meFilterAssignments('<?php echo e($prefix); ?>', this.value)">

    <div class="me-tabs">
        <?php $__currentLoopData = $catalog; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $code => $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <button type="button" class="me-tab <?php echo e($loop->first ? 'active' : ''); ?>"
                data-target="me-panel-<?php echo e($prefix); ?>-<?php echo e($code); ?>" onclick="meShowTab('<?php echo e($prefix); ?>', '<?php echo e($code); ?>')">
                <?php echo e($group['label']); ?> <span class="badge bg-light text-dark"><?php echo e($group['subjects']->count()); ?></span>
            </button>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <?php $__currentLoopData = $catalog; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $code => $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="me-panel" id="me-panel-<?php echo e($prefix); ?>-<?php echo e($code); ?>" style="<?php echo e($loop->first ? '' : 'display:none;'); ?>">
            <?php if($group['subjects']->isEmpty()): ?>
                <p class="text-muted small mb-0">No <?php echo e($group['label']); ?> subjects configured yet.</p>
            <?php endif; ?>
            <?php $__currentLoopData = $group['subjects']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $subject): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="me-subject-row" data-search="<?php echo e(strtolower($subject['name'])); ?>">
                    <div class="me-subject-name"><?php echo e($subject['name']); ?> <span class="text-muted">(<?php echo e($subject['code']); ?>)</span>
                    </div>
                    <div class="me-subject-papers">
                        <?php if($subject['total_papers'] <= 1): ?>
                            <label class="me-paper-check">
                                <input type="checkbox" name="assignments[]" value="<?php echo e($subject['id']); ?>:1">
                                Assign
                            </label>
                        <?php else: ?>
                            <?php for($p = 1; $p <= $subject['total_papers']; $p++): ?>
                                <label class="me-paper-check">
                                    <input type="checkbox" name="assignments[]" value="<?php echo e($subject['id']); ?>:<?php echo e($p); ?>">
                                    P<?php echo e($p); ?>

                                </label>
                            <?php endfor; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div><?php /**PATH /home/u453625278/domains/kamssamock.com/public_html/resources/views/marks-entrants/partials/assignment-picker.blade.php ENDPATH**/ ?>