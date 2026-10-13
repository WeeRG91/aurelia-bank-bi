@extends('layouts.app')

@section('title', 'My Dashboards')

@section('content')
    <div
        id="employee-dashboard-app"
        data-employee-dashboard
    ></div>

    <script
        id="employee-dashboard-bootstrap"
        type="application/json"
    >
        {!! $bootstrapJson !!}
    </script>
@endsection
