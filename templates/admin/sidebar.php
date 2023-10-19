<?php

global $proler__;

$title = 'Upgrade to PRO Now';
$tagline = 'Get maximum/minimum quantity support with PRO';
$button = 'Upgrade PRO';

// change on prostate
if( $proler__['prostate'] == 'installed' ){
    $title = 'Activate PRO Now';
    $tagline = 'Get maximum/minimum quantity support with PRO';
    $button = 'Activate PRO';
}
elseif( $proler__['prostate'] == 'activated' ){
    $title = 'PRO License Activated';
    $tagline = 'Get our exclusive PRO Support 24/7 <br>only for you.';
}

?>
<div class="proler-sidebar">
    <div class="sidebar_top">
        <h1><?php echo esc_html( $title ); ?></h1>
        <div class="tagline_side"><?php echo esc_html( $tagline ); ?></div>
        <?php if( $proler__['prostate'] != 'activated' ) : ?>
        <div><a href="<?php echo esc_url( $proler__['plugin']['free_url'] ); ?>" target="_blank"><?php echo esc_html( $button ); ?></a></div>
        <?php endif; ?>
    </div>
    <div class="sidebar_bottom">
        <ul>
            <li>
                <strong>Maximum Quantity:</strong> Set an upper limit on the number of items a customer can purchase for a specific product.
            </li>
            <li>
                <strong>Minimum Quantity:</strong> Establish a minimum number of items that a customer must purchase for a specific product.
            </li>
            <li>
                <strong>Rocket speed support:</strong> Most of our customer's problem solved within 24 hours of their first contact.
            </li>
            </ul>
    </div>
    <div class="support">
        <h3>Dedicated Support Team</h3>
        <p>Our support is what makes us No.1. We are available round the clock for any support.</p>
        <p><a href="<?php echo esc_url( $proler__['plugin']['request_quote'] ); ?>" target="_blank">Send Request</a></p>
    </div>
</div>