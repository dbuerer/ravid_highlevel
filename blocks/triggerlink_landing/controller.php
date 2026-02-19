<?php

namespace Concrete\Package\RavidHighlevel\Block\TriggerlinkLanding;

use Concrete\Core\Block\BlockController;
use Concrete\Core\Editor\LinkAbstractor;
use Concrete\Core\Feature\Features;
use Concrete\Core\Feature\UsesFeatureInterface;
use Concrete\Core\File\Tracker\FileTrackableInterface;
use Concrete\Core\File\Tracker\RichTextExtractor;


/**
 * The controller for the content block.
 *
 * @package Blocks
 * @subpackage Content
 *
 * @author Andrew Embler <andrew@concrete5.org>
 * @copyright  Copyright (c) 2003-2022 concreteCMS. (http://www.concretecms.org)
 * @license    http://www.concretecms.org/license/     MIT License
 */
class Controller extends BlockController implements FileTrackableInterface, UsesFeatureInterface
{
   
    public $content;
    public $triggerlink;

    protected $btTable = 'btRavidHighLevelTriggerLink';
    protected $btInterfaceWidth = 800;
    protected $btInterfaceHeight = 600;
    protected $btCacheBlockRecord = false;
    protected $btCacheBlockOutput = false;
    protected $btCacheBlockOutputOnPost = false;
    protected $btSupportsInlineEdit = false;
    protected $btSupportsInlineAdd = false;
    protected $btCacheBlockOutputForRegisteredUsers = false;
    protected $btCacheBlockOutputOnEditMode = false;
    protected $btCacheBlockOutputLifetime = 0; //until manually updated or cleared

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Block\BlockController::$btExportContentColumns
     */
    protected $btExportContentColumns = ['content'];

    /**
     * {@inhertdoc}.
     */
    public function getRequiredFeatures(): array
    {
        return [
            Features::IMAGERY,
        ];
    }

    /**
     * @return string
     */
    public function getBlockTypeDescription()
    {
        return t('Creates a text based landing element out of post parmeters found in a trigger link');
    }

    /**
     * @return string
     */
    public function getBlockTypeName()
    {
        return t('Trigger Link Landing');
    }

    public function cacheBlockOutputForRegisteredUsers()
    {
        if ($this->btCacheBlockOutputForRegisteredUsers === null) {
            $this->btCacheBlockOutputForRegisteredUsers = strrpos($this->content, 'data-scs') === false;
        }

        return $this->btCacheBlockOutputForRegisteredUsers;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return LinkAbstractor::translateFrom($this->content);
    }

    /**
     * @return string
     */
    public function getSearchableContent()
    {
        return $this->content;
    }

    /**
     * @param string $str
     *
     * @return array|string|string[]
     */
    public function br2nl($str)
    {
        return str_replace(["\r\n", "<br />\n", "<br />\r\n"], "\n", $str);
    }

    /**
     * @return void
     */
    public function view()
    {
        $content=$this->getContent();
        //get fields
        preg_match_all('/{{\w*}}/', $content, $arrFields);

        $keyvalue=[];
        foreach($arrFields[0] AS $thisKey){
            $value=$_REQUEST[str_replace('{{','',str_replace('}}','',$thisKey))]??"!no match for field $thisKey!";
            $content=str_replace($thisKey,$value,$content);
        }

        $this->set('content', $content);
    }

    /**
     * @return string
     */
    public function getContentEditMode()
    {
        return LinkAbstractor::translateFromEditMode($this->content);
    }

    /**
     * @param array<string,string> $args
     */
    public function save($args)
    {
        if (isset($args['content'])) {
            $args['content'] = LinkAbstractor::translateTo($args['content']);
        }
        parent::save($args);
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\File\Tracker\FileTrackableInterface::getUsedFiles()
     */
    public function getUsedFiles()
    {
        return $this->app->make(RichTextExtractor::class)->extractFiles($this->content);
    }
    
}
