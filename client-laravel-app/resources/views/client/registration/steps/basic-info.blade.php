<!-- Basic Information Step -->
<div class="space-y-6">
    <!-- Profile Photo -->
    <div class="flex items-center space-x-6">
        <div class="flex-shrink-0">
            <img class="h-20 w-20 rounded-full object-cover" 
                 src="{{ $user->profile_image_url ?? asset('images/default-avatar.png') }}" 
                 alt="Profile photo" id="profilePreview">
        </div>
        <div>
            <label for="profile_image" class="block text-sm font-medium text-gray-700 mb-2">
                Profile Photo
            </label>
            <div class="flex items-center space-x-3">
                <input type="file" id="profile_image" name="profile_image" accept="image/*"
                       class="hidden" onchange="previewImage(this)">
                <button type="button" onclick="document.getElementById('profile_image').click()"
                        class="bg-white border border-gray-300 rounded-lg px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Choose Photo
                </button>
                <span class="text-sm text-gray-500">JPG, PNG up to 5MB</span>
            </div>
        </div>
    </div>

    <!-- Personal Information -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">
                First Name *
            </label>
            <input type="text" id="first_name" name="first_name" required
                   value="{{ old('first_name', $user->first_name) }}"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('first_name') border-red-500 @enderror">
            @error('first_name')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">
                Last Name *
            </label>
            <input type="text" id="last_name" name="last_name" required
                   value="{{ old('last_name', $user->last_name) }}"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('last_name') border-red-500 @enderror">
            @error('last_name')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <!-- Bio -->
    <div>
        <label for="bio" class="block text-sm font-medium text-gray-700 mb-2">
            Tell us about yourself
        </label>
        <textarea id="bio" name="bio" rows="4"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('bio') border-red-500 @enderror"
                  placeholder="Share your interests, motivations for volunteering, and what makes you unique...">{{ old('bio', $user->profile?->bio) }}</textarea>
        @error('bio')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
        <p class="text-sm text-gray-500 mt-1">This helps organizations understand your background and interests.</p>
    </div>

    <!-- Personal Details -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="date_of_birth" class="block text-sm font-medium text-gray-700 mb-2">
                Date of Birth
            </label>
            <input type="date" id="date_of_birth" name="date_of_birth"
                   value="{{ old('date_of_birth', $user->profile?->date_of_birth?->format('Y-m-d')) }}"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('date_of_birth') border-red-500 @enderror">
            @error('date_of_birth')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="gender" class="block text-sm font-medium text-gray-700 mb-2">
                Gender
            </label>
            <select id="gender" name="gender"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('gender') border-red-500 @enderror">
                <option value="">Select Gender</option>
                <option value="male" {{ old('gender', $user->profile?->gender) === 'male' ? 'selected' : '' }}>Male</option>
                <option value="female" {{ old('gender', $user->profile?->gender) === 'female' ? 'selected' : '' }}>Female</option>
                <option value="other" {{ old('gender', $user->profile?->gender) === 'other' ? 'selected' : '' }}>Other</option>
                <option value="prefer_not_to_say" {{ old('gender', $user->profile?->gender) === 'prefer_not_to_say' ? 'selected' : '' }}>Prefer not to say</option>
            </select>
            @error('gender')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <!-- Contact Information -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="phone_number" class="block text-sm font-medium text-gray-700 mb-2">
                Phone Number
            </label>
            <input type="tel" id="phone_number" name="phone_number"
                   value="{{ old('phone_number', $user->profile?->phone_number) }}"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('phone_number') border-red-500 @enderror"
                   placeholder="+1 (555) 123-4567">
            @error('phone_number')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="emergency_contact" class="block text-sm font-medium text-gray-700 mb-2">
                Emergency Contact
            </label>
            <input type="text" id="emergency_contact" name="emergency_contact"
                   value="{{ old('emergency_contact', $user->profile?->emergency_contact) }}"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('emergency_contact') border-red-500 @enderror"
                   placeholder="Name and phone number">
            @error('emergency_contact')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <!-- Privacy Preferences -->
    <div class="bg-gray-50 rounded-lg p-4">
        <h4 class="text-sm font-medium text-gray-900 mb-3">Privacy Preferences</h4>
        <div class="space-y-3">
            <label class="flex items-center">
                <input type="checkbox" name="is_public" value="1" 
                       {{ old('is_public', $user->profile?->is_public ?? true) ? 'checked' : '' }}
                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                <span class="ml-2 text-sm text-gray-700">Make my profile visible to organizations</span>
            </label>
            
            <label class="flex items-center">
                <input type="checkbox" name="allow_messages" value="1" 
                       {{ old('allow_messages', $user->profile?->allow_messages ?? true) ? 'checked' : '' }}
                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                <span class="ml-2 text-sm text-gray-700">Allow organizations to contact me directly</span>
            </label>
        </div>
    </div>
</div>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('profilePreview').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>