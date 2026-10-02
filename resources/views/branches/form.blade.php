@extends('layouts.app', ['heading' => $branch->exists ? 'Edit branch' : 'Tambah branch'])
@section('content')
<div class="content">
    <div class="form-card">
        <form method="POST" action="{{ $action }}">
            @csrf
            @if($method !== 'POST') @method($method) @endif
            <div class="form-grid">
                <label>Code<input name="code" value="{{ old('code', $branch->code) }}" placeholder="BOG" required></label>
                <label>Nama cabang<input name="name" value="{{ old('name', $branch->name) }}" required></label>
                <label>Kota<input name="city" value="{{ old('city', $branch->city) }}" required></label>
                <label>Network CIDR <small class="muted">Boleh diisi nanti</small><input name="network_cidr" value="{{ old('network_cidr', $branch->network_cidr) }}" placeholder="192.168.10.0/24"></label>
                <label>Gateway IP <small class="muted">Boleh diisi nanti</small><input name="gateway_ip" value="{{ old('gateway_ip', $branch->gateway_ip) }}" placeholder="192.168.10.1"></label>
                <label>Status<select name="status">@foreach(['healthy','warning','critical','unreachable'] as $status)<option value="{{ $status }}" @selected(old('status', $branch->status ?: 'unreachable') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></label>
            </div>
            <div class="form-actions"><a class="back" href="{{ route('branches.index') }}">Cancel</a><button class="refresh" type="submit">Save branch</button></div>
        </form>
    </div>
</div>
@endsection
