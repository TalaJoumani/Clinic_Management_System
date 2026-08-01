<?php
namespace App\Image;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUpload
{
    public function upload(UploadedFile $file,string $folder):string
    {
     $filename=Str::uuid().'.'. $file->getClientOriginalExtension();
     $path=$file->storeAs($folder,$filename,'public');
     return $path;
    }

    public function delete(?string $path):bool
    {
     if($path && Storage::disk('public')->exists($path)){
         return Storage::disk('public')->delete($path);
     }
     return false;
    }
}