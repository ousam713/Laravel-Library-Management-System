<form method="POST" action="{{ route('admin.create') }}" enctype="multipart/form-data">
    <div class="modal fade text-left" id="createUserModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title"> {{__('Create New User')}} </h4>
                </div>
                <div class="modal-body">
                     @csrf  
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control @error('first_name') is-invalid @enderror" 
                                id="first_name" name="first_name" value="{{ old('first_name') }}" required>
                            @error('first_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control @error('last_name') is-invalid @enderror" 
                                id="last_name" name="last_name" value="{{ old('last_name') }}" required>
                            @error('last_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                id="email" name="email" value="{{ old('email') }}" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                id="password" name="password" required>
                        <div class="form-text">Password must be at least 8 characters with mixed case and numbers.</div>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                            <option value="">Select Role</option>
                            <option value="student" {{ old('role') == 'student' ? 'selected' : '' }}>étudiant</option>
                            <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>admin</option>
                            <option value="librarian" {{ old('role') == 'librarian' ? 'selected' : '' }}>bibliothécaire</option>
                        </select>
                        @error('role')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="hidden" name="is_active" value="0"> <!-- This ensures a value is always sent -->
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" 
                            {{ old('is_active', true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active User</label>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="#" class="btn btn-secondary me-md-2" data-dismiss="modal" aria-label="Close">Cancel</a>
                        <button type="submit" class="btn btn-primary">Create User</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>