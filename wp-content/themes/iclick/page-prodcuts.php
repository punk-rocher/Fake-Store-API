<?php
/*
Template Name: Products
*/

get_header(); ?>

<style>
    .products {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
    }

    .product-card {
        background: white;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        transition: transform 0.2s;
    }

    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .product-image {
        width: 100%;
        height: 200px;
        object-fit: contain;
        margin-bottom: 15px;
    }

    .product-title {
        font-size: 18px;
        font-weight: bold;
        color: #333;
        margin-bottom: 10px;
        min-height: 50px;
    }

    .product-price {
        font-size: 20px;
        color: #28a745;
        font-weight: bold;
        margin-bottom: 10px;
    }

    .product-category {
        display: inline-block;
        background: #e9ecef;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 12px;
        margin-bottom: 10px;
        color: #495057;
    }

    .product-description {
        color: #666;
        font-size: 14px;
        line-height: 1.5;
    }

    .loading {
        text-align: center;
        padding: 40px;
        font-size: 18px;
        color: #666;
    }
</style>

<div class="container">
    <h1>All Products</h1>

    <?php
    $args = [
        'post_type' => 'product',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'title',
        'order' => 'ASC'
    ];

    $loop = new WP_Query($args);

    if ($loop->have_posts()): ?>
        <div class="products">
            <?php while ($loop->have_posts()):
                $loop->the_post();
                global $product; ?>
                <div class="product-card">
                    <a href="<?php the_permalink(); ?>">
                        <?php if (has_post_thumbnail()) {
                            the_post_thumbnail('medium', ['class' => 'product-image']);
                        } else {
                            echo '<img src="' . esc_url('https://via.placeholder.com/280x200?text=No+Image') . '" class="product-image">';
                        } ?>
                    </a>
                    <div class="product-category">
                        <?php
                        $terms = wp_get_post_terms(get_the_ID(), 'product_cat', ['fields' => 'names']);
                        echo !empty($terms) ? esc_html($terms[0]) : 'Uncategorized';
                        ?>
                    </div>
                    <div class="product-title"><?php the_title(); ?></div>
                    <div class="product-price"><?php echo $product ? $product->get_price_html() : ''; ?></div>
                    <div class="product-description"><?php the_content(); ?></div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p class="loading">No products found</p>
    <?php endif;
    wp_reset_postdata(); ?>
</div>

<?php get_footer(); ?>