@extends('layouts.app')

@section('title', 'Color Scheme Demo')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">AfricaVLP Color Scheme Demo</h1>
            <p class="lead">This page demonstrates the new color scheme across all UI components.</p>
        </div>
    </div>

    <!-- Color Palette -->
    <div class="row mb-5">
        <div class="col-12">
            <h2>Color Palette</h2>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="p-4 text-center text-white" style="background-color: #8A2B13;">
                        <h5>Button Color</h5>
                        <code>#8A2B13</code>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="p-4 text-center" style="background-color: #F4F2C9; color: #8A2B13;">
                        <h5>Button Hover</h5>
                        <code>#F4F2C9</code>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="p-4 text-center text-white" style="background-color: #1789A7;">
                        <h5>Card Color</h5>
                        <code>#1789A7</code>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Button Examples -->
    <div class="row mb-5">
        <div class="col-12">
            <h2>Button Examples</h2>
            <div class="row">
                <div class="col-md-6">
                    <h4>Standard Buttons</h4>
                    <button type="button" class="btn btn-primary me-2 mb-2">Primary Button</button>
                    <button type="button" class="btn btn-secondary me-2 mb-2">Secondary Button</button>
                    <button type="button" class="btn btn-success me-2 mb-2">Success Button</button>
                    <button type="button" class="btn btn-danger me-2 mb-2">Danger Button</button>
                    <button type="button" class="btn btn-warning me-2 mb-2">Warning Button</button>
                    <button type="button" class="btn btn-info me-2 mb-2">Info Button</button>
                </div>
                <div class="col-md-6">
                    <h4>Button Sizes</h4>
                    <button type="button" class="btn btn-primary btn-lg me-2 mb-2">Large Button</button>
                    <button type="button" class="btn btn-primary me-2 mb-2">Default Button</button>
                    <button type="button" class="btn btn-primary btn-sm me-2 mb-2">Small Button</button>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-6">
                    <h4>Outline Buttons</h4>
                    <button type="button" class="btn btn-outline-primary me-2 mb-2">Outline Primary</button>
                    <button type="button" class="btn btn-outline-secondary me-2 mb-2">Outline Secondary</button>
                </div>
                <div class="col-md-6">
                    <h4>Form Buttons</h4>
                    <input type="submit" value="Submit Button" class="btn me-2 mb-2">
                    <input type="button" value="Input Button" class="btn me-2 mb-2">
                    <input type="reset" value="Reset Button" class="btn me-2 mb-2">
                </div>
            </div>
        </div>
    </div>

    <!-- Card Examples -->
    <div class="row mb-5">
        <div class="col-12">
            <h2>Card Examples</h2>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Basic Card</h5>
                        </div>
                        <div class="card-body">
                            <p class="card-text">This is a basic card with the new color scheme. All text should be white on the teal background.</p>
                            <a href="#" class="btn btn-primary">Card Button</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Card with Form</h5>
                        </div>
                        <div class="card-body">
                            <form>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" placeholder="Enter email">
                                </div>
                                <div class="mb-3">
                                    <label for="message" class="form-label">Message</label>
                                    <textarea class="form-control" id="message" rows="3" placeholder="Enter message"></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Submit</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Card with List</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">First item</li>
                                <li class="list-group-item">Second item</li>
                                <li class="list-group-item">Third item</li>
                            </ul>
                        </div>
                        <div class="card-footer">
                            <small>Card footer content</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table in Card -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Data Table Example</h5>
                </div>
                <div class="card-body">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>John Doe</td>
                                <td>john@example.com</td>
                                <td><span class="badge bg-success">Active</span></td>
                                <td>
                                    <button class="btn btn-sm btn-primary me-1">Edit</button>
                                    <button class="btn btn-sm btn-danger">Delete</button>
                                </td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>Jane Smith</td>
                                <td>jane@example.com</td>
                                <td><span class="badge bg-warning">Pending</span></td>
                                <td>
                                    <button class="btn btn-sm btn-primary me-1">Edit</button>
                                    <button class="btn btn-sm btn-danger">Delete</button>
                                </td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>Bob Johnson</td>
                                <td>bob@example.com</td>
                                <td><span class="badge bg-danger">Inactive</span></td>
                                <td>
                                    <button class="btn btn-sm btn-primary me-1">Edit</button>
                                    <button class="btn btn-sm btn-danger">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerts -->
    <div class="row mb-5">
        <div class="col-12">
            <h2>Alert Examples</h2>
            <div class="alert alert-primary" role="alert">
                This is a primary alert with the new color scheme.
            </div>
            <div class="alert alert-success" role="alert">
                This is a success alert message.
            </div>
            <div class="alert alert-warning" role="alert">
                This is a warning alert message.
            </div>
            <div class="alert alert-danger" role="alert">
                This is a danger alert message.
            </div>
            <div class="alert alert-info" role="alert">
                This is an info alert with the card color scheme.
            </div>
        </div>
    </div>

    <!-- Badges -->
    <div class="row mb-5">
        <div class="col-12">
            <h2>Badge Examples</h2>
            <span class="badge bg-primary me-2">Primary Badge</span>
            <span class="badge bg-secondary me-2">Secondary Badge</span>
            <span class="badge bg-success me-2">Success Badge</span>
            <span class="badge bg-danger me-2">Danger Badge</span>
            <span class="badge bg-warning me-2">Warning Badge</span>
            <span class="badge bg-info me-2">Info Badge</span>
        </div>
    </div>

    <!-- Pagination -->
    <div class="row mb-5">
        <div class="col-12">
            <h2>Pagination Example</h2>
            <nav aria-label="Page navigation">
                <ul class="pagination">
                    <li class="page-item">
                        <a class="page-link" href="#" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    <li class="page-item"><a class="page-link" href="#">1</a></li>
                    <li class="page-item active"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item">
                        <a class="page-link" href="#" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>

    <!-- Modal Example -->
    <div class="row mb-5">
        <div class="col-12">
            <h2>Modal Example</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">
                Launch Demo Modal
            </button>

            <!-- Modal -->
            <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Modal with Custom Colors</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            This modal demonstrates the custom color scheme for modal headers and buttons.
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary">Save changes</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dropdown Example -->
    <div class="row mb-5">
        <div class="col-12">
            <h2>Dropdown Example</h2>
            <div class="dropdown">
                <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                    Dropdown Button
                </button>
                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                    <li><a class="dropdown-item" href="#">Action</a></li>
                    <li><a class="dropdown-item" href="#">Another action</a></li>
                    <li><a class="dropdown-item active" href="#">Active item</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="#">Separated link</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Progress Bars -->
    <div class="row mb-5">
        <div class="col-12">
            <h2>Progress Bar Examples</h2>
            <div class="progress mb-3">
                <div class="progress-bar" role="progressbar" style="width: 25%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">25%</div>
            </div>
            <div class="progress mb-3">
                <div class="progress-bar progress-bar-striped" role="progressbar" style="width: 50%" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100">50%</div>
            </div>
            <div class="progress mb-3">
                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 75%" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100">75%</div>
            </div>
        </div>
    </div>
</div>
@endsection
