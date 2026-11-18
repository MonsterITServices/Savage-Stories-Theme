<?php
if ( post_password_required() ) {
    return;
}
?>

<div id="comments" class="comments-area">

    <?php if ( have_comments() ) : ?>
        <h2 class="comments-title">
            <?php
            $comment_count = get_comments_number();
            if ( '1' === $comment_count ) {
                printf( esc_html__( '1 Thought on &ldquo;%1$s&rdquo;', 'strangebooks' ), '<span>' . wp_kses_post( get_the_title() ) . '</span>' );
            } else {
                printf(
                    esc_html( _nx( '%1$s Thought on &ldquo;%2$s&rdquo;', '%1$s Thoughts on &ldquo;%2$s&rdquo;', $comment_count, 'comments title', 'strangebooks' ) ),
                    number_format_i18n( $comment_count ),
                    '<span>' . wp_kses_post( get_the_title() ) . '</span>'
                );
            }
            ?>
        </h2>

        <ol class="comment-list">
            <?php
            wp_list_comments( array(
                'style'       => 'ol',
                'short_ping'  => true,
                'avatar_size' => 42,
            ) );
            ?>
        </ol>

        <?php
        the_comments_navigation();

        if ( ! comments_open() ) :
            ?>
            <p class="no-comments"><?php esc_html_e( 'Comments are closed.', 'strangebooks' ); ?></p>
            <?php
        endif;

    endif;

    comment_form( array(
        'title_reply'          => __( 'Leave a Comment', 'strangebooks' ),
        'title_reply_to'       => __( 'Leave a Reply to %s', 'strangebooks' ),
        'cancel_reply_link'    => __( 'Cancel Reply', 'strangebooks' ),
        'label_submit'         => __( 'Post Comment', 'strangebooks' ),
        'class_submit'         => 'book-btn comment-submit-btn',
        'comment_notes_before' => '',
    ) );
    ?>

</div>