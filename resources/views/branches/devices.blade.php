@extends('layouts.app', ['heading' => $branch->name . ' devices'])
@section('content')
    <div class="content">
        <div class="toolbar">
            <div>
                <p class="muted">{{ $branch->network_cidr }} · Gateway {{ $branch->gateway_ip }}</p>
            </div>
            <div class="toolbar-actions">
                <form class="filters"><input name="search" value="{{ request('search') }}"
                        placeholder="Search IP, hostname, MAC..."><select name="status">
                        <option value="">All status</option>
                        @foreach (['online', 'offline', 'warning', 'unknown'] as $v)
                            <option value="{{ $v }}" @selected(request('status') === $v)>{{ ucfirst($v) }}</option>
                        @endforeach
                    </select>
                    <button class="refresh">Filter</button>
                </form><a class="refresh" href="{{ route('branches.devices.create', $branch) }}">＋ Device</a>
            </div>
        </div>
        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>IP address</th>
                        <th>Identity</th>
                        <th>Type / vendor</th>
                        <th>Latency</th>
                        <th>Packet loss</th>
                        <th>Last seen</th>
                        <th>Severity</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($devices as $device)
                        <tr>
                            <td><span class="status-dot {{ $device->status }}"></span>{{ ucfirst($device->status) }}</td>
                            <td><a class="mono link"
                                    href="{{ route('devices.show', $device) }}">{{ $device->ip_address }}</a></td>
                            <td><strong>{{ $device->device_name }}</strong><small>{{ $device->hostname ?: 'No hostname' }}
                                    · {{ $device->mac_address ?: 'No MAC' }}</small></td>
                            <td>{{ ucfirst(str_replace('_', ' ', $device->device_type)) }}<small>{{ $device->vendor ?: 'Unknown vendor' }}</small>
                            </td>
                            <td>{{ $device->latency_ms ?? '—' }} ms</td>
                            <td>{{ $device->packet_loss ?? '—' }}%</td>
                            <td>{{ $device->last_seen?->diffForHumans() ?: 'Never' }}</td>
                            <td><span class="severity {{ $device->severity }}">{{ ucfirst($device->severity) }}</span>
                            </td>
                            <td class="actions"><a href="{{ route('devices.edit', $device) }}">Edit</a>
                                <form method="POST" action="{{ route('devices.destroy', $device) }}"
                                    onsubmit="return confirm('Hapus device ini?')">@csrf
                                    @method('DELETE')<button>Delete</button></form>
                            </td>
                    </tr>@empty<tr>
                            <td colspan="9" class="empty">No devices found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $devices->links() }}</div>
    </div>
@endsection
