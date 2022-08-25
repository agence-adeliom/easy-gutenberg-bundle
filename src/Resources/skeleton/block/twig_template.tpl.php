<ul>
    <li>Your block at <code><?php

declare(strict_types=1);

    echo $helper->getFileLink(sprintf('%s/%s', $root_directory, $block_path), sprintf('%s', $block_path)); ?></code></li>
    <li>Your template at <code><?php echo $helper->getFileLink(sprintf('%s/%s', $root_directory, $relative_path), sprintf('%s', $relative_path)); ?></code></li>
</ul>