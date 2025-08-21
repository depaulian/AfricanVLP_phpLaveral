@extends('layouts.client')

@section('title', 'Mobile Volunteering Dashboard')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/mobile-volunteering.css') }}">
@endpush

@section('content')
<div class="mobile-volunteering-dashboard" id="mobileVolunteeringApp">
    <!-- Mobile Header -->
    <div class="mobile-header">
        <div class="header-content">
            <h1 class="header-title">Volunteering</h1>
            <div class="header-actions">
                <button class="btn-icon" @click="refreshLocation" :disabled="locationLoading">
                    <i class="fas fa-location-arrow" :class="{ 'fa-spin': locationLoading }"></i>
                </button>
                <button class="btn-icon" @click="toggleOfflineMode">
                    <i class="fas" :class="offlineMode ? 'fa-wifi-slash' : 'fa-wifi'"></i>
                </button>
            </div>
        </div>
        
        <!-- Location Status -->
        <div class="location-status" v-if="userLocation">
            <i class="fas fa-map-marker-alt"></i>
            <span>@{{ userLocation.city || 'Current Location' }}</span>
            <span class="accuracy" v-if="userLocation.accuracy">
                (±@{{ Math.round(userLocation.accuracy) }}m)
            </span>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <div class="action-grid">
            <button class="action-card" @click="showNearbyOpportunities" :disabled="!userLocation">
                <i class="fas fa-search-location"></i>
                <span>Find Nearby</span>
                <small>@{{ nearbyCount }} opportunities</small>
            </button>
            
            <button class="action-card" @click="showCheckInOptions" :disabled="!activeAssignments.length">
                <i class="fas fa-clock"></i>
                <span>Check In</span>
                <small>@{{ activeAssignments.length }} active</small>
            </button>
            
            <button class="action-card" @click="showTimeLogForm">
                <i class="fas fa-stopwatch"></i>
                <span>Log Time</span>
                <small>Quick entry</small>
            </button>
            
            <button class="action-card" @click="showOfflineData">
                <i class="fas fa-download"></i>
                <span>Offline</span>
                <small>@{{ offlineDataCount }} items</small>
            </button>
        </div>
    </div>

    <!-- Active Check-ins -->
    <div class="active-checkins" v-if="activeCheckIns.length">
        <h3>Active Sessions</h3>
        <div class="checkin-list">
            <div class="checkin-card" v-for="checkIn in activeCheckIns" :key="checkIn.id">
                <div class="checkin-info">
                    <h4>@{{ checkIn.opportunity.title }}</h4>
                    <div class="checkin-time">
                        <i class="fas fa-clock"></i>
                        <span>@{{ checkIn.formatted_duration }}</span>
                    </div>
                </div>
                <button class="btn btn-danger btn-sm" @click="checkOut(checkIn)">
                    Check Out
                </button>
            </div>
        </div>
    </div>

    <!-- Nearby Opportunities -->
    <div class="nearby-opportunities" v-if="nearbyOpportunities.length">
        <div class="section-header">
            <h3>Nearby Opportunities</h3>
            <button class="btn-link" @click="refreshNearby">
                <i class="fas fa-refresh"></i>
            </button>
        </div>
        
        <div class="opportunity-list">
            <div class="opportunity-card" v-for="opportunity in nearbyOpportunities" :key="opportunity.id">
                <div class="opportunity-header">
                    <h4>@{{ opportunity.title }}</h4>
                    <span class="distance" v-if="opportunity.location.distance">
                        @{{ Math.round(opportunity.location.distance * 10) / 10 }}km
                    </span>
                </div>
                
                <div class="opportunity-meta">
                    <div class="organization">
                        <img :src="opportunity.organization.logo" :alt="opportunity.organization.name" class="org-logo">
                        <span>@{{ opportunity.organization.name }}</span>
                    </div>
                    
                    <div class="category" :style="{ backgroundColor: opportunity.category.color }">
                        @{{ opportunity.category.name }}
                    </div>
                </div>
                
                <p class="opportunity-description">@{{ opportunity.description }}</p>
                
                <div class="opportunity-actions">
                    <button class="btn btn-primary btn-sm" @click="viewOpportunity(opportunity)">
                        View Details
                    </button>
                    <button class="btn btn-outline-primary btn-sm" @click="getDirections(opportunity)">
                        <i class="fas fa-directions"></i>
                        Directions
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="recent-activity">
        <h3>Recent Activity</h3>
        <div class="activity-list">
            <div class="activity-item" v-for="activity in recentActivity" :key="activity.id">
                <div class="activity-icon" :class="activity.type">
                    <i :class="activity.icon"></i>
                </div>
                <div class="activity-content">
                    <h5>@{{ activity.title }}</h5>
                    <p>@{{ activity.description }}</p>
                    <small>@{{ formatDate(activity.created_at) }}</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Offline Status Banner -->
    <div class="offline-banner" v-if="offlineMode">
        <i class="fas fa-wifi-slash"></i>
        <span>You're working offline. Data will sync when connection is restored.</span>
        <button @click="syncOfflineData" :disabled="syncing">
            <i class="fas fa-sync" :class="{ 'fa-spin': syncing }"></i>
            Sync Now
        </button>
    </div>

    <!-- Modals -->
    <div class="modal fade" id="checkInModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Check In</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form @submit.prevent="performCheckIn">
                        <div class="mb-3">
                            <label class="form-label">Select Assignment</label>
                            <select v-model="checkInForm.assignment_id" class="form-select" required>
                                <option value="">Choose assignment...</option>
                                <option v-for="assignment in activeAssignments" :key="assignment.id" :value="assignment.id">
                                    @{{ assignment.opportunity.title }}
                                </option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Notes (Optional)</label>
                            <textarea v-model="checkInForm.notes" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3" v-if="config.check_in.photo_required || checkInForm.photo">
                            <label class="form-label">Photo</label>
                            <input type="file" @change="handlePhotoUpload" accept="image/*" class="form-control">
                            <div class="photo-preview" v-if="checkInForm.photoPreview">
                                <img :src="checkInForm.photoPreview" alt="Check-in photo">
                            </div>
                        </div>
                        
                        <div class="location-info" v-if="userLocation">
                            <h6>Location</h6>
                            <p>
                                <i class="fas fa-map-marker-alt"></i>
                                Lat: @{{ userLocation.latitude.toFixed(6) }}, 
                                Lng: @{{ userLocation.longitude.toFixed(6) }}
                                <span class="accuracy">(±@{{ Math.round(userLocation.accuracy) }}m)</span>
                            </p>
                        </div>
                        
                        <div class="modal-actions">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="checkingIn">
                                <i class="fas fa-clock" v-if="checkingIn"></i>
                                @{{ checkingIn ? 'Checking In...' : 'Check In' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Time Log Modal -->
    <div class="modal fade" id="timeLogModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Log Volunteer Time</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form @submit.prevent="submitTimeLog">
                        <div class="mb-3">
                            <label class="form-label">Assignment</label>
                            <select v-model="timeLogForm.assignment_id" class="form-select" required>
                                <option value="">Choose assignment...</option>
                                <option v-for="assignment in userAssignments" :key="assignment.id" :value="assignment.id">
                                    @{{ assignment.opportunity.title }}
                                </option>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date</label>
                                <input type="date" v-model="timeLogForm.log_date" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Hours</label>
                                <input type="number" v-model="timeLogForm.hours_logged" step="0.5" min="0.5" max="24" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Start Time</label>
                                <input type="time" v-model="timeLogForm.start_time" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">End Time</label>
                                <input type="time" v-model="timeLogForm.end_time" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea v-model="timeLogForm.description" class="form-control" rows="3" placeholder="What did you accomplish?"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Photo (Optional)</label>
                            <input type="file" @change="handleTimeLogPhoto" accept="image/*" class="form-control">
                            <div class="photo-preview" v-if="timeLogForm.photoPreview">
                                <img :src="timeLogForm.photoPreview" alt="Time log photo">
                            </div>
                        </div>
                        
                        <div class="modal-actions">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="submittingTimeLog">
                                <i class="fas fa-clock" v-if="submittingTimeLog"></i>
                                @{{ submittingTimeLog ? 'Submitting...' : 'Submit Time Log' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/mobile-volunteering.js') }}"></script>
@endpush