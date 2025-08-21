<div class="px-6 py-8">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Basic Information</h2>
        <p class="text-gray-600 mt-2">Let's start with some basic information about you</p>
    </div>

    <form id="wizardForm" action="{{ route('registration.wizard.store', ['step' => 'basic_info']) }}" method="POST" class="space-y-6">
        @csrf
        
        <!-- Profile Photo -->
        <div class="flex items-center space-x-6">
            <div class="shrink-0">
                <img class="h-16 w-16 object-cover rounded-full" 
                     src="{{ $user->profile_image_url ?? asset('images/default-avatar.png') }}" 
                     alt="Profile photo">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Profile Photo</label>
                <div class="mt-1 flex items-center space-x-4">
                    <input type="file" id="profile_image" name="profile_image" accept="image/*" class="hidden">
                    <button type="button" onclick="document.getElementById('profile_image').click()"
                            class="bg-white py-2 px-3 border border-gray-300 rounded-md shadow-sm text-sm leading-4 font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Change Photo
                    </button>
                    <span class="text-sm text-gray-500">JPG, PNG up to 2MB</span>
                </div>
            </div>
        </div>

        <!-- Name Fields -->
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

        <!-- Email (readonly) -->
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                Email Address
            </label>
            <input type="email" id="email" name="email" readonly
                   value="{{ $user->email }}"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500">
            <p class="text-sm text-gray-500 mt-1">Email cannot be changed during registration</p>
        </div>

        <!-- Phone Number -->
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

        <!-- Date of Birth -->
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

        <!-- Bio -->
        <div>
            <label for="bio" class="block text-sm font-medium text-gray-700 mb-2">
                Bio
            </label>
            <textarea id="bio" name="bio" rows="4"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('bio') border-red-500 @enderror"
                      placeholder="Tell us about yourself, your interests, and what motivates you to volunteer...">{{ old('bio', $user->profile?->bio) }}</textarea>
            @error('bio')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
            <p class="text-sm text-gray-500 mt-1">Maximum 1000 characters</p>
        </div>

        <!-- Why Volunteering -->
        <div>
            <label for="why_volunteering" class="block text-sm font-medium text-gray-700 mb-2">
                Why do you want to volunteer?
            </label>
            <textarea id="why_volunteering" name="why_volunteering" rows="3"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('why_volunteering') border-red-500 @enderror"
                      placeholder="Share your motivation for volunteering...">{{ old('why_volunteering', $user->profile?->why_volunteering) }}</textarea>
            @error('why_volunteering')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>
    </form>
</div>

@push('scripts')
<script>
// Profile image preview
document.getElementById('profile_image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.querySelector('img[alt="Profile photo"]').src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
});

// Character count for bio
const bioTextarea = document.getElementById('bio');
const maxLength = 1000;

function updateCharCount() {
    const remaining = maxLength - bioTextarea.value.length;
    let countElement = document.getElementById('bio-count');
    
    if (!countElement) {
        countElement = document.createElement('p');
        countElement.id = 'bio-count';
        countElement.className = 'text-sm text-gray-500 mt-1';
        bioTextarea.parentNode.appendChild(countElement);
    }
    
    countElement.textContent = `${remaining} characters remaining`;
    
    if (remaining < 0) {
        countElement.className = 'text-sm text-red-500 mt-1';
    } else if (remaining < 100) {
        countElement.className = 'text-sm text-yellow-500 mt-1';
    } else {
        countElement.className = 'text-sm text-gray-500 mt-1';
    }
}

bioTextarea.addEventListener('input', updateCharCount);
updateCharCount(); // Initial count
</script>
@endpush