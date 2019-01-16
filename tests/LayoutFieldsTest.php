<?php

use Corcel\Acf\Field\Repeater;
use Corcel\Acf\Field\FlexibleContent;
use Corcel\Model\Post;
use Corcel\Acf\Tests\TestCase;
use Corcel\Model\User;
use Corcel\Model\Attachment;

/**
 * Class LayoutFieldsTest.
 *
 * @author Junior Grossi <juniorgro@gmail.com>
 */
class LayoutFieldsTest extends TestCase
{
    /**
     * @var Post
     */
    protected $post;

    /**
     * Setup a base $this->post object to represent the page with the content fields.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->post = $this->createAcfPost();
    }

    /**
     * Create a sample post with acf fields
     */
    protected function createAcfPost()
    {
        $post = factory(Post::class)->create();

        // repeater #1
        $this->createAcfField($post, 'fake_repeater', '2', 'repeater');
        $this->createAcfField($post, 'fake_repeater_0_repeater_text', 'First text');
        $this->createAcfField($post, 'fake_repeater_0_repeater_radio', 'blue', 'radio_button');
        $this->createAcfField($post, 'fake_repeater_1_repeater_text', 'Second text');
        $this->createAcfField($post, 'fake_repeater_1_repeater_radio', 'red', 'radio_button');


        // repeater #1
        $users = factory(User::class, 2)->create();
        $files = factory(Attachment::class, 2)->states('file')->create();
        $relationships = factory(Post::class, 5)->states('page')->create();

        $this->createAcfField($post, 'fake_repeater_2', '2', 'repeater');
        $this->createAcfField($post, 'fake_repeater_2_0_fake_user', $users->first()->ID, 'user');
        $this->createAcfField($post, 'fake_repeater_2_0_fake_file', $files->first()->ID, 'file');
        $this->createAcfField($post, 'fake_repeater_2_0_fake_relationship', serialize($relationships->take(2)->pluck('ID')), 'relationship');
        $this->createAcfField($post, 'fake_repeater_2_1_fake_user', $users->last()->ID, 'user');
        $this->createAcfField($post, 'fake_repeater_2_1_fake_file', $files->last()->ID, 'file');
        $this->createAcfField($post, 'fake_repeater_2_1_fake_relationship', serialize($relationships->take(1)->pluck('ID')), 'relationship');


        // flexible content
        $post2 = factory(Post::class)->create();
        $posts = factory(Post::class, 2)->create();
        $rootAcf = $this->createAcfField($post, 'fake_flexible_content', 'a:3:{i:0;s:11:"normal_text";i:1;s:12:"related_post";i:2;s:14:"multiple_posts";}', 'flexible_content');
        $this->createAcfField(
            $post,
            'fake_flexible_content_0_text',
            'Lorem ipsum',
            [],
            [
                'post_parent' => $rootAcf->ID,
                'post_content' => 'a:11:{s:4:"type";s:4:"text";s:12:"instructions";s:0:"";s:8:"required";i:0;s:17:"conditional_logic";i:0;s:7:"wrapper";a:3:{s:5:"width";s:0:"";s:5:"class";s:0:"";s:2:"id";s:0:"";}s:13:"parent_layout";s:13:"589c18bcf10da";s:13:"default_value";s:0:"";s:11:"placeholder";s:0:"";s:7:"prepend";s:0:"";s:6:"append";s:0:"";s:9:"maxlength";s:0:"";}',
                'post_excerpt' => 'text',
            ]
        );
        $this->createAcfField(
            $post,
            'fake_flexible_content_1_post',
            $post2->ID,
            'post_object',
            [
                'post_parent' => $rootAcf->ID,
                'post_content' => 'a:12:{s:4:"type";s:11:"post_object";s:12:"instructions";s:0:"";s:8:"required";i:0;s:17:"conditional_logic";i:0;s:7:"wrapper";a:3:{s:5:"width";s:0:"";s:5:"class";s:0:"";s:2:"id";s:0:"";}s:13:"parent_layout";s:13:"589c18dfc9b28";s:9:"post_type";a:0:{}s:8:"taxonomy";a:0:{}s:10:"allow_null";i:0;s:8:"multiple";i:0;s:13:"return_format";s:6:"object";s:2:"ui";i:1;}',
                'post_excerpt' => 'post',
            ]
        );
        $this->createAcfField(
            $post,
            'fake_flexible_content_2_post',
            serialize($posts->pluck('ID')->toArray()),
            'post_object',
            [
                'post_parent' => $rootAcf->ID,
                'post_content' => 'a:12:{s:4:"type";s:11:"post_object";s:12:"instructions";s:0:"";s:8:"required";i:0;s:17:"conditional_logic";i:0;s:7:"wrapper";a:3:{s:5:"width";s:0:"";s:5:"class";s:0:"";s:2:"id";s:0:"";}s:13:"parent_layout";s:13:"589c1ee35ec27";s:9:"post_type";a:0:{}s:8:"taxonomy";a:0:{}s:10:"allow_null";i:0;s:8:"multiple";i:1;s:13:"return_format";s:6:"object";s:2:"ui";i:1;}',
                'post_excerpt' => 'post',
            ]
        );


        return $post;
    }

    public function testRepeaterField()
    {
        $repeater = new Repeater($this->post);
        $repeater->process('fake_repeater');
        $fields = $repeater->get()->toArray();

        $this->assertEquals('First text', $fields[0]['repeater_text']);
        $this->assertEquals('Second text', $fields[1]['repeater_text']);
        $this->assertEquals('blue', $fields[0]['repeater_radio']);
        $this->assertEquals('red', $fields[1]['repeater_radio']);
    }

    public function testComplexRepeaterField()
    {
        $repeater = new Repeater($this->post);
        $repeater->process('fake_repeater_2');
        $fields = $repeater->get()->toArray();

        $this->assertEquals('admin', $fields[0]['fake_user']->user_login);
        $this->assertEquals('admin', $fields[1]['fake_user']->user_login);
        $this->assertEquals(2, $fields[0]['fake_relationship']->count());
        $this->assertEquals(1, $fields[1]['fake_relationship']->count());
        $this->assertInstanceOf(Post::class, $fields[0]['fake_relationship']->first());
    }


    public function testFlexibleContentField()
    {
        $flex = new FlexibleContent($this->post);
        $flex->process('fake_flexible_content');
        $layout = $flex->get();

        $this->assertEquals(3, $layout->count());

        $this->assertEquals('normal_text', $layout[0]->type);
        $this->assertEquals('Lorem ipsum', $layout[0]->fields->text);

        $this->assertEquals('related_post', $layout[1]->type);
        $this->assertInstanceOf(Post::class, $layout[1]->fields->post);

        $this->assertEquals('multiple_posts', $layout[2]->type);
        $this->assertEquals(2, $layout[2]->fields->post->count());
        $this->assertInstanceOf(Post::class, $layout[2]->fields->post->first());
    }
}
