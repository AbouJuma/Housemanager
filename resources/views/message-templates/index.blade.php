@extends('layouts.app')

@section('title', 'Message Templates')

@section('page-heading')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="h4 mb-0">Message Templates</h1>
        <a href="{{ route('message-templates.create') }}" class="btn btn-primary">
            <i class="bi bi-plus me-1"></i> Create Template
        </a>
    </div>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="message-templates-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($templates as $template)
                            <tr>
                                <td>
                                    <span class="badge bg-primary">
                                        {{ Str::title(str_replace('_', ' ', $template->type)) }}
                                    </span>
                                </td>
                                <td>{{ $template->name }}</td>
                                <td>
                                    @if($template->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('message-templates.edit', $template->id) }}" class="btn btn-sm btn-primary">
                                            Edit
                                        </a>
                                        <form action="{{ route('message-templates.destroy', $template->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this template?')">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4">
                                    No message templates found. <a href="{{ route('message-templates.create') }}">Create one</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Prevent DataTables from initializing on this table
            const table = document.getElementById('message-templates-table');
            if (table) {
                table.classList.add('no-datatables');
            }
        });
    </script>
@endsection
