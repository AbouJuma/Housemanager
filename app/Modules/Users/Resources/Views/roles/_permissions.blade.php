@php($assigned = $assigned ?? [])

@foreach ($permissionGroups as $group => $permissions)
    <fieldset class="mb-3">
        <legend class="h6 text-capitalize">{{ $group }}</legend>
        <div class="row g-2">
            @foreach ($permissions as $name => $description)
                <div class="col-md-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $name }}"
                               id="permission-{{ $name }}" @checked(in_array($name, $assigned, true))>
                        <label class="form-check-label" for="permission-{{ $name }}" title="{{ $description }}">
                            {{ $name }}
                        </label>
                    </div>
                </div>
            @endforeach
        </div>
    </fieldset>
@endforeach
