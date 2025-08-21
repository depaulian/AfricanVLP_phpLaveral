# Cloudinary Integration Guide

## Overview

The AfricaVLP Laravel applications use Cloudinary as the primary cloud storage solution for all image and file uploads. This integration provides scalable, optimized, and secure file storage with advanced image transformation capabilities.

## Architecture

### Core Components

1. **CloudinaryService** - Main service class for Cloudinary API interactions
2. **CloudinaryStorageAdapter** - High-level adapter for context-aware file storage
3. **Configuration** - Environment-based configuration in `config/services.php`

### File Storage Contexts

The system supports multiple storage contexts with appropriate folder organization:

- **Profile Images** - User profile pictures with multiple sizes (thumbnail, medium, large)
- **Organization Images** - Organization logos and banners
- **Event Images** - Event photos and promotional images
- **Forum Attachments** - File attachments in alumni forums
- **Support Attachments** - Files attached to support tickets
- **Feedback Attachments** - Files attached to user feedback
- **Resource Files** - Educational and organizational resources
- **Documents** - User verification documents and certificates
- **Portfolio Items** - User portfolio files and images
- **General Files** - Miscellaneous file uploads
- **General Images** - Miscellaneous image uploads

## Configuration

### Environment Variables

Add the following variables to your `.env` file:

```env
# Cloudinary Configuration
CLOUDINARY_CLOUD_NAME=your_cloud_name
CLOUDINARY_API_KEY=your_api_key
CLOUDINARY_API_SECRET=your_api_secret
CLOUDINARY_UPLOAD_PRESET=your_upload_preset
```

### Services Configuration

The configuration is automatically loaded from `config/services.php`:

```php
'cloudinary' => [
    'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
    'api_key' => env('CLOUDINARY_API_KEY'),
    'api_secret' => env('CLOUDINARY_API_SECRET'),
    'secure' => true,
    'upload_preset' => env('CLOUDINARY_UPLOAD_PRESET'),
],
```

## Usage Examples

### Basic File Upload

```php
use App\Services\CloudinaryStorageAdapter;

// Inject the service
public function __construct(CloudinaryStorageAdapter $storageAdapter)
{
    $this->storageAdapter = $storageAdapter;
}

// Upload a profile image
public function uploadProfileImage(Request $request)
{
    $file = $request->file('image');
    
    // Validate file
    $validation = $this->storageAdapter->validateFile($file, 'profile_image');
    if (!$validation['valid']) {
        return back()->withErrors($validation['errors']);
    }
    
    // Store file
    $result = $this->storageAdapter->store($file, 'profile_image', [
        'user_id' => auth()->id()
    ]);
    
    // Update user record
    auth()->user()->update([
        'profile_image_public_id' => $result['public_id'],
        'profile_image_url' => $result['original_url'],
        'profile_image_thumbnail' => $result['thumbnail_url'],
    ]);
    
    return back()->with('success', 'Profile image updated successfully');
}
```

### Organization Image Upload

```php
public function uploadOrganizationImage(Request $request, $organizationId)
{
    $file = $request->file('image');
    
    $result = $this->storageAdapter->store($file, 'organization_image', [
        'organization_id' => $organizationId
    ]);
    
    Organization::find($organizationId)->update([
        'image_public_id' => $result['public_id'],
        'image_url' => $result['url'],
    ]);
}
```

### Forum Attachment Upload

```php
public function uploadForumAttachment(Request $request, $threadId)
{
    $file = $request->file('attachment');
    
    $result = $this->storageAdapter->store($file, 'forum_attachment', [
        'thread_id' => $threadId
    ]);
    
    ForumAttachment::create([
        'thread_id' => $threadId,
        'filename' => $result['original_filename'],
        'public_id' => $result['public_id'],
        'url' => $result['url'],
        'file_size' => $result['file_size'],
        'format' => $result['format'],
    ]);
}
```

### File Deletion

```php
public function deleteFile($publicId)
{
    $deleted = $this->storageAdapter->delete($publicId);
    
    if ($deleted) {
        // Remove from database
        // Update related records
    }
}
```

### Image Transformations

```php
// Generate thumbnail URL
$thumbnailUrl = $this->storageAdapter->generateUrl($publicId, [
    'width' => 150,
    'height' => 150,
    'crop' => 'fill',
    'quality' => 'auto',
    'format' => 'auto'
]);

// Generate optimized image URL
$optimizedUrl = $this->storageAdapter->generateUrl($publicId, [
    'quality' => 'auto:best',
    'format' => 'auto',
    'dpr' => 'auto'
]);
```

## File Validation

The system includes comprehensive file validation:

### Image Validation
- **Allowed types**: JPEG, PNG, GIF, WebP
- **Maximum size**: 10MB
- **Maximum dimensions**: 4000x4000 pixels

### General File Validation
- **Maximum size**: 50MB
- **Blocked types**: Executable files (.exe, .msi, etc.)
- **Security checks**: MIME type validation

## Folder Structure

Files are organized in Cloudinary using a hierarchical folder structure:

```
africavlp/
├── profiles/
│   ├── user_123/
│   │   ├── original/
│   │   ├── thumbnail/
│   │   ├── medium/
│   │   └── large/
├── organizations/
│   └── org_456/
├── events/
│   └── event_789/
├── forums/
│   └── thread_101/
├── support/
│   └── ticket_202/
├── feedback/
│   └── feedback_303/
├── resources/
│   └── resource_404/
├── documents/
│   └── user_123/
├── portfolio/
│   └── user_123/
├── general/
└── images/
```

## Error Handling

The system includes comprehensive error handling:

```php
try {
    $result = $this->storageAdapter->store($file, 'profile_image', $metadata);
} catch (Exception $e) {
    Log::error('File upload failed: ' . $e->getMessage());
    return back()->withErrors(['file' => 'Upload failed. Please try again.']);
}
```

## Performance Optimization

### Automatic Optimizations
- **Quality optimization**: Automatic quality adjustment based on content
- **Format optimization**: Automatic format selection (WebP when supported)
- **Responsive images**: Multiple sizes for different use cases
- **CDN delivery**: Global CDN for fast image delivery

### Caching
- **Browser caching**: Long-term caching headers
- **CDN caching**: Edge caching for global performance
- **Application caching**: File metadata caching in Laravel

## Security Features

### Upload Security
- **File type validation**: MIME type and extension checking
- **Size limits**: Configurable file size limits
- **Malware scanning**: Cloudinary's built-in security scanning
- **Access control**: Signed URLs for private files

### API Security
- **Signature generation**: Secure API signatures for uploads
- **Timestamp validation**: Request timestamp validation
- **Rate limiting**: API rate limiting protection

## Monitoring and Analytics

### Usage Tracking
- **Storage usage**: Monitor storage consumption
- **Bandwidth usage**: Track CDN bandwidth usage
- **Transformation usage**: Monitor image transformation credits

### Error Monitoring
- **Upload failures**: Track and log upload failures
- **API errors**: Monitor Cloudinary API errors
- **Performance metrics**: Track upload and delivery performance

## Troubleshooting

### Common Issues

1. **Upload Failures**
   - Check API credentials
   - Verify file size and type limits
   - Check network connectivity

2. **Image Not Displaying**
   - Verify public_id is correct
   - Check image URL generation
   - Confirm image exists in Cloudinary

3. **Performance Issues**
   - Enable automatic optimizations
   - Use appropriate image transformations
   - Implement proper caching

### Debug Mode

Enable debug logging in your `.env`:

```env
LOG_LEVEL=debug
```

Check logs for detailed error information:

```bash
tail -f storage/logs/laravel.log | grep Cloudinary
```

## Migration from Local Storage

If migrating from local storage:

1. **Backup existing files**
2. **Upload files to Cloudinary** using batch upload scripts
3. **Update database records** with new Cloudinary URLs
4. **Test file access** thoroughly
5. **Remove local files** after verification

## Best Practices

1. **Use appropriate contexts** for different file types
2. **Implement proper validation** before uploads
3. **Handle errors gracefully** with user-friendly messages
4. **Use transformations** for optimized delivery
5. **Monitor usage** and costs regularly
6. **Implement cleanup** for orphaned files
7. **Use signed URLs** for private content
8. **Cache file metadata** for performance

## Support

For issues related to Cloudinary integration:

1. Check the Laravel logs for detailed error messages
2. Verify Cloudinary dashboard for upload status
3. Test API connectivity using Cloudinary's tools
4. Review file validation rules and limits
5. Contact Cloudinary support for API-specific issues

## API Reference

### CloudinaryStorageAdapter Methods

- `store(UploadedFile $file, string $context, array $metadata)` - Store a file
- `delete(string $publicId)` - Delete a file
- `getFileInfo(string $publicId)` - Get file information
- `generateUrl(string $publicId, array $transformations)` - Generate transformation URL
- `validateFile(UploadedFile $file, string $context)` - Validate file before upload

### CloudinaryService Methods

- `uploadProfileImage(UploadedFile $file, string $userId)` - Upload profile image with sizes
- `uploadOrganizationImage(UploadedFile $file, string $organizationId)` - Upload organization image
- `uploadEventImage(UploadedFile $file, string $eventId)` - Upload event image
- `uploadForumAttachment(UploadedFile $file, string $threadId)` - Upload forum attachment
- `uploadSupportAttachment(UploadedFile $file, string $ticketId)` - Upload support attachment
- `uploadFeedbackAttachment(UploadedFile $file, string $feedbackId)` - Upload feedback attachment
- `uploadResource(UploadedFile $file, string $resourceId)` - Upload resource file
- `deleteFile(string $publicId)` - Delete file from Cloudinary
- `getFileInfo(string $publicId)` - Get file information
- `generateTransformationUrl(string $publicId, array $transformations)` - Generate URL with transformations
