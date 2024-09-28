@extends('email_template.layout')

@section('content')
    <tr>
        <td>
            <b>AWS EC2 Server Error Report</b>
            <hr>

            {!! $html !!}

        </td>
    </tr>
@endsection
