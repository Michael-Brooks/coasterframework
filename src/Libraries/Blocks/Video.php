<?php namespace CoasterCms\Libraries\Blocks;

use CoasterCms\Models\Block;
use CoasterCms\Models\BlockVideoCache;
use GuzzleHttp\Client;
use View;

class Video extends String_
{
    /**
     * @var array
     */
    protected static $_cachedVideoData = [];

    /**
     * @var string
     */
    protected $_renderDataName = 'video';

    /**
     * Video constructor.
     * @param Block $block
     */
    public function __construct(Block $block)
    {
        parent::__construct($block);
        $this->_displayViewDirs[] = 'vidoes';
    }

    /**
     * Display video using info from youtube API
     * @param string $content
     * @param array $options
     * @return string
     */
    public function display($content, $options = [])
    {
        if (!empty($content)) {
            if (!($videoInfo = $this->_cache($content))) {
                return 'Video does not exist, it may have been removed from youtube';
            }
            return $this->_renderDisplayView($options, $videoInfo);
        } else {
            return '';
        }
    }

    /**
     * @param string $content
     * @return string
     */
    public function edit($content)
    {
        $this->_editViewData['placeHolder'] = [];
        if ($this->_editViewData['videoInfo'] = $this->_cache($content)) {
            $this->_editViewData['placeHolder'][$content] = $this->_editViewData['videoInfo']->snippet->title;
        }
        return parent::edit($content);
    }

    /**
     * Save video with and youtube video API data to cache
     * @param array $postContent
     * @return static
     */
    public function submit($postContent)
    {
        if (!empty($postContent['select'])) {
            if ($videoData = $this->_dl('videos', ['id' => $postContent, 'part' => 'id,snippet'])) {
                $cachedVideoData = BlockVideoCache::where('videoId', '=', $postContent)->first() ?: new BlockVideoCache;
                $cachedVideoData->videoId = $postContent;
                $cachedVideoData->videoInfo = serialize($videoData);
                $cachedVideoData->save();
            }
        }
        return $this->save(!empty($postContent['select']) ? $postContent['select'] : '');
    }

    /**
     * Return cached youtube video API data or fetch if not cached
     * @param $videoId
     * @return mixed
     */
    protected function _cache($videoId)
    {
        if (!array_key_exists($videoId, static::$_cachedVideoData)) {
            if ($videoData = BlockVideoCache::where('videoId', '=', $videoId)->first()) {
                static::$_cachedVideoData[$videoId] = unserialize($videoData->videoInfo);
            } else {
                static::$_cachedVideoData[$videoId] = $this->_dl('videos', ['id' => $videoId, 'part' => 'id,snippet']);
            }
        }
        return static::$_cachedVideoData[$videoId];
    }

    /**
     * Download youtube API data
     * @param $request
     * @param array $params
     * @return null
     */
    protected function _dl($request, $params = [])
    {
        try {
            $youTube = new Client(['base_uri' => 'https://www.googleapis.com/youtube/v3/']);
            $params =  $params + ['key' => config('coaster::key.yt_server')];
            $response = $youTube->request('GET', $request, ['query' => $params]);
            $data = json_decode($response->getBody());
            return !empty($data) && !empty($data->items[0]) ? $data->items[0] : null;
        } catch (\Exception $e) {
            return null;
        }
    }

}