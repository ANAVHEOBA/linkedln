<?php

namespace App\Http\Requests\Instagram;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreatePostRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'media_type' => 'required|string|in:IMAGE,VIDEO,CAROUSEL_ALBUM,REELS,STORY',
            'media_source' => 'required|string|in:file,url',
            'caption' => 'nullable|string|max:2200',
            'location_id' => 'nullable|string',
            'thumbnail' => 'nullable|required_if:media_type,VIDEO,REELS|string|url', // Thumbnail URL for videos
            'share_to_feed' => 'nullable|boolean',
        ];

        $mediaRules = $this->getMediaRules();
        return array_merge($rules, $mediaRules);
    }

    protected function getMediaRules()
    {
        $rules = [];
        $mediaSource = $this->input('media_source', 'file');

        switch ($this->input('media_type')) {
            case 'IMAGE':
                $rules['media'] = $this->getImageRules($mediaSource);
                break;

            case 'VIDEO':
            case 'REELS':
                $rules['media'] = $this->getVideoRules($mediaSource);
                if ($mediaSource === 'url') {
                    $rules['thumbnail'] = ['required', 'url', 'active_url'];
                }
                break;

            case 'CAROUSEL_ALBUM':
                $rules['media'] = 'required|array|min:2|max:10';
                if ($mediaSource === 'file') {
                    $rules['media.*'] = [
                        'required',
                        Rule::when(fn() => $this->hasVideo(), [
                            'mimes:mp4,mov,jpeg,png,jpg',
                            'max:102400', // 100MB for videos
                        ], [
                            'mimes:jpeg,png,jpg',
                            'max:8192', // 8MB for images
                        ])
                    ];
                    $rules['thumbnails'] = Rule::when(fn() => $this->hasVideo(), [
                        'required',
                        'array',
                        'min:1',
                    ]);
                    $rules['thumbnails.*'] = Rule::when(fn() => $this->hasVideo(), [
                        'required',
                        'url',
                        'active_url',
                    ]);
                } else {
                    $rules['media.*'] = ['required', 'url', 'active_url'];
                    $rules['media_types'] = 'required|array|min:2|max:10';
                    $rules['media_types.*'] = 'required|in:IMAGE,VIDEO';
                    $rules['thumbnails'] = 'required_if:media_types.*,VIDEO|array';
                    $rules['thumbnails.*'] = 'required_with:media_types.*,VIDEO|url|active_url';
                }
                break;

            case 'STORY':
                $rules['media'] = $this->getStoryRules($mediaSource);
                if ($mediaSource === 'url' && $this->isVideo($this->input('media'))) {
                    $rules['thumbnail'] = ['required', 'url', 'active_url'];
                }
                break;
        }

        return $rules;
    }

    protected function getImageRules($source)
    {
        return $source === 'file' 
            ? ['required', 'file', 'image', 'mimes:jpeg,png,jpg', 'max:8192']
            : ['required', 'url', 'active_url'];
    }

    protected function getVideoRules($source)
    {
        return $source === 'file' 
            ? [
                'required',
                'file',
                'mimes:mp4,mov',
                'max:102400',
                'mimetypes:video/mp4,video/quicktime',
            ]
            : ['required', 'url', 'active_url'];
    }

    protected function getStoryRules($source)
    {
        return $source === 'file'
            ? [
                'required',
                'file',
                'mimes:jpeg,png,jpg,mp4,mov',
                'max:102400',
            ]
            : ['required', 'url', 'active_url'];
    }

    protected function hasVideo()
    {
        return collect($this->input('media_types', []))->contains('VIDEO');
    }

    protected function isVideo($url)
    {
        if (!$url) return false;
        $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));
        return in_array($extension, ['mp4', 'mov']);
    }

    public function messages()
    {
        return [
            'media_type.required' => 'Please specify the type of media you want to post',
            'media_type.in' => 'Invalid media type selected',
            'media_source.required' => 'Please specify the media source (file or url)',
            'media_source.in' => 'Invalid media source. Must be file or url',
            'media.required' => 'Please provide media file or URL',
            'media.image' => 'The file must be an image',
            'media.mimes' => 'Invalid file format',
            'media.max' => 'File size exceeds the limit',
            'media.url' => 'Invalid media URL provided',
            'media.active_url' => 'The provided URL is not accessible',
            'thumbnail.required' => 'Thumbnail is required for video content',
            'thumbnail.url' => 'Invalid thumbnail URL',
            'thumbnail.active_url' => 'The thumbnail URL is not accessible',
            'caption.max' => 'Caption exceeds Instagram\'s character limit',
            'media.*.required' => 'All carousel items are required',
            'media.*.url' => 'Invalid URL for carousel item',
            'media.*.active_url' => 'Inaccessible URL for carousel item',
            'thumbnails.required' => 'Thumbnails are required for video content',
            'thumbnails.*.url' => 'Invalid thumbnail URL',
            'media_types.required' => 'Media types must be specified for carousel',
            'media_types.*.in' => 'Invalid media type in carousel',
        ];
    }
}