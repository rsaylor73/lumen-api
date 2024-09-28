@extends('email_template.layout')

@section('content')
    <tr>
        <td>
            <b>AWS Degraded Server</b>
            <hr>

            We have detected the following EC2 in a degraded state: {{ $instanceId }}

        </td>
    </tr>
@endsection
