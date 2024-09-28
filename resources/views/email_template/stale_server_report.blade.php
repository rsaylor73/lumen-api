@extends('email_template.layout')

@section('content')
    <tr>
        <td>
            <b>{{ $dns }}.virtualjobshadow.com</b>
            <hr>
            We have detected your server might be getting a little old. If you are no longer using this server please delete it.
        </td>
    </tr>
@endsection
