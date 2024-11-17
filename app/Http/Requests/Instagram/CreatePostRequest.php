<?php

namespace App\Http\Requests\Instagram;

use Illuminate\Foundation\Http\FormRequest;

class CreatePostRequest extends FormRequest
{
    public function authorize()
    {
        return true; // or auth()->check() if you want to ensure user is logged in
    }

    public function rules()
    {
        $rules = [
            'media_type' => 'required|string|in:IMAGE,VIDEO,CAROUSEL_ALBUM,REELS,STORY',
            'caption' => 'nullable|string|max:2200', // Instagram's caption limit
            'location_id' => 'nullable|string',
        ];

        switch ($this->input('media_type')) {
            case 'IMAGE':
                $rules['media'] = 'required|file|image|mimes:jpeg,png,jpg|max:8192'; // 8MB max
                break;

            case 'VIDEO':
            case 'REELS':
                $rules['media'] = [
                    'required',
                    'file',
                    'mimes:mp4,mov,avi',
                    'max:102400', // 100MB max
                    'mimetypes:video/mp4,video/quicktime',
                ];
                $rules['share_to_feed'] = 'nullable|boolean'; // for reels
                break;

            case 'CAROUSEL_ALBUM':
                $rules['media.*'] = [
                    'required',
                    'file',
                    'mimes:jpeg,png,jpg,mp4,mov',
                    'max:8192',
                ];
                $rules['media'] = 'required|array|min:2|max:10'; // Instagram allows 2-10 items
                break;

            case 'STORY':
                $rules['media'] = [
                    'required',
                    'file',
                    'mimes:jpeg,png,jpg,mp4,mov',
                    'max:8192',
                ];
                break;
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'media_type.required' => 'Please specify the type of media you want to post',
            'media_type.in' => 'Invalid media type selected',
            'media.required' => 'Please select a media file to upload',
            'media.image' => 'The file must be an image',
            'media.mimes' => 'Invalid file format',
            'media.max' => 'File size exceeds the limit',
            'caption.max' => 'Caption exceeds Instagram\'s character limit',
            'media.*.required' => 'All carousel items are required',
            'media.*.mimes' => 'Invalid file format in carousel',
            'media.*.max' => 'Carousel item exceeds size limit',
        ];
    }
}