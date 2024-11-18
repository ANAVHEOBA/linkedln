<?php

namespace App\Http\Requests\Instagram;

use Illuminate\Foundation\Http\FormRequest;

class CreatePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // or auth()->check()
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'media_type' => 'required|string|in:IMAGE,VIDEO,CAROUSEL_ALBUM,REELS,STORY',
            'caption' => 'nullable|string|max:2200',
            'location_id' => 'nullable|string',
        ];

        switch ($this->input('media_type')) {
            case 'IMAGE':
                $rules['media'] = 'required_without:media_url|file|image|mimes:jpeg,png,jpg|max:8192';
                $rules['media_url'] = 'required_without:media|nullable|url';
                break;

            case 'VIDEO':
                $rules['media'] = [
                    'required_without:media_url',
                    'file',
                    'mimes:mp4,mov',
                    'max:102400',
                    'mimetypes:video/mp4,video/quicktime'
                ];
                $rules['media_url'] = 'required_without:media|nullable|url';
                $rules['thumbnail'] = 'nullable|file|image|mimes:jpeg,png,jpg|max:8192';
                $rules['thumbnail_url'] = 'nullable|url';
                break;

            case 'REELS':
                $rules['media'] = [
                    'required_without:media_url',
                    'file',
                    'mimes:mp4,mov',
                    'max:102400',
                    'mimetypes:video/mp4,video/quicktime'
                ];
                $rules['media_url'] = 'required_without:media|nullable|url';
                $rules['thumbnail'] = 'nullable|file|image|mimes:jpeg,png,jpg|max:8192';
                $rules['thumbnail_url'] = 'nullable|url';
                $rules['share_to_feed'] = 'nullable|boolean';
                $rules['audio_name'] = 'nullable|string|max:255';
                break;

            case 'CAROUSEL_ALBUM':
                // For file uploads
                $rules['media'] = 'required_without:media_urls|array|min:2|max:10';
                $rules['media.*'] = [
                    'required',
                    'file',
                    'mimes:jpeg,png,jpg,mp4,mov',
                    'max:8192',
                ];
                
                // For URL uploads
                $rules['media_urls'] = 'required_without:media|array|min:2|max:10';
                $rules['media_urls.*'] = 'required|url';
                
                // For thumbnails (when videos are included)
                $rules['thumbnails'] = 'nullable|array';
                $rules['thumbnails.*'] = 'nullable|file|image|mimes:jpeg,png,jpg|max:8192';
                $rules['thumbnail_urls'] = 'nullable|array';
                $rules['thumbnail_urls.*'] = 'nullable|url';
                break;

            case 'STORY':
                $rules['media'] = [
                    'required_without:media_url',
                    'file',
                    'mimes:jpeg,png,jpg,mp4,mov',
                    'max:8192',
                ];
                $rules['media_url'] = 'required_without:media|nullable|url';
                $rules['thumbnail'] = 'nullable|file|image|mimes:jpeg,png,jpg|max:8192';
                $rules['thumbnail_url'] = 'nullable|url';
                break;
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'media_type.required' => 'Please specify the type of media you want to post',
            'media_type.in' => 'Invalid media type selected',
            'media.required_without' => 'Please provide either a media file or URL',
            'media_url.required_without' => 'Please provide either a media file or URL',
            'media.file' => 'Invalid file upload',
            'media.image' => 'The file must be an image',
            'media.mimes' => 'Invalid file format',
            'media.mimetypes' => 'Invalid video format. Please use MP4 or MOV',
            'media.max' => 'File size exceeds the limit',
            'media_url.url' => 'Invalid media URL format',
            'caption.max' => 'Caption exceeds Instagram\'s character limit',
            
            // Carousel specific
            'media.array' => 'Invalid carousel format',
            'media.min' => 'Carousel must have at least 2 items',
            'media.max' => 'Carousel cannot have more than 10 items',
            'media.*.required' => 'All carousel items are required',
            'media.*.mimes' => 'Invalid file format in carousel',
            'media.*.max' => 'Carousel item exceeds size limit',
            'media_urls.min' => 'Carousel must have at least 2 items',
            'media_urls.max' => 'Carousel cannot have more than 10 items',
            'media_urls.*.url' => 'Invalid URL format in carousel',
            
            // Thumbnail specific
            'thumbnail.image' => 'Thumbnail must be an image',
            'thumbnail.mimes' => 'Invalid thumbnail format',
            'thumbnail.max' => 'Thumbnail size exceeds the limit',
            'thumbnail_url.url' => 'Invalid thumbnail URL',
            'thumbnails.*.image' => 'All thumbnails must be images',
            'thumbnails.*.mimes' => 'Invalid thumbnail format in carousel',
            'thumbnails.*.max' => 'Thumbnail size exceeds the limit',
            
            // Other
            'share_to_feed.boolean' => 'Share to feed must be true or false',
            'audio_name.max' => 'Audio name is too long',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('share_to_feed')) {
            $this->merge([
                'share_to_feed' => filter_var($this->share_to_feed, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }
}