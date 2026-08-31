<?php

namespace Kollus\Component\Container;

class UploadFile extends AbstractContainer
{
    /**
     * @var string
     */
    private $upload_file_key;

    /**
     * @var integer
     */
    private $media_content_id;

    /**
     * @var string
     */
    private $title;

    /**
     * @var integer
     */
    private $transcoding_stage;

    /**
     * @var string
     */
    private $transcoding_stage_name;

    /**
     * @var integer
     */
    private $transcoding_progress;

    /**
     * @var integer
     */
    private $created_at;

    /**
     * @var integer
     */
    private $transcoded_at;

    /**
     * @return string
     */
    public function getUploadFileKey()
    {
        return $this->upload_file_key;
    }

    /**
     * @param string $upload_file_key
     */
    public function setUploadFileKey($upload_file_key)
    {
        $this->upload_file_key = $upload_file_key;
    }

    /**
     * @return int
     */
    public function getMediaContentId()
    {
        return $this->media_content_id;
    }

    /**
     * @param int $media_content_id
     */
    public function setMediaContentId($media_content_id)
    {
        $this->media_content_id = $media_content_id;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return int
     */
    public function getTranscodingStage()
    {
        return $this->transcoding_stage;
    }

    /**
     * @param int $transcoding_stage
     */
    public function setTranscodingStage($transcoding_stage)
    {
        $this->transcoding_stage = $transcoding_stage;
    }

    /**
     * @return string
     */
    public function getTranscodingStageName()
    {
        return $this->transcoding_stage_name;
    }

    /**
     * @param string $transcoding_stage_name
     */
    public function setTranscodingStageName($transcoding_stage_name)
    {
        $this->transcoding_stage_name = $transcoding_stage_name;
    }

    /**
     * @return int
     */
    public function getTranscodingProgress()
    {
        return $this->transcoding_progress;
    }

    /**
     * @param int $transcoding_progress
     */
    public function setTranscodingProgress($transcoding_progress)
    {
        $this->transcoding_progress = $transcoding_progress;
    }

    /**
     * @return int
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * @param int $created_at
     */
    public function setCreatedAt($created_at)
    {
        $this->created_at = $created_at;
    }

    /**
     * @return int
     */
    public function getTranscodedAt()
    {
        return $this->transcoded_at;
    }

    /**
     * @param int $transcoded_at
     */
    public function setTranscodedAt($transcoded_at)
    {
        $this->transcoded_at = $transcoded_at;
    }
}
