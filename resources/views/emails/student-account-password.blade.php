{{--
============================================================
STUDENT ACCOUNT PASSWORD EMAIL
------------------------------------------------------------
Variables:
$student->first_name
$loginEmail -> generated login email
$initialPassword -> generated initial password
Keep text flush-left (no indentation) or markdown breaks.
============================================================
--}}

<x-mail::message>
  <p style="text-align:center; margin-bottom:24px;">
    <img src="{{ asset('images/moodlink-logo.png') }}" alt="Moodlink" style="height:48px;">
  </p>

  Hi {{ $student->first_name }}!

  Welcome to Moodlink! We are happy to inform you that your Moodlink student account has been successfully created and
  activated. Your account has been reviewed and verified by the Guidance and Counseling Unit, which means you can
  now sign in and start using the Moodlink App.

  Below are the initial credentials you will use to log in to the Moodlink App for the very first time. Please keep them
  safe, as they give access to your personal student account.

  Initial Password: {{ $initialPassword }}

  When you sign in for the first time, please use the initial password shown above. After you have successfully logged
  in, we strongly recommend that you change your password right away to a new one that only you know. This helps keep
  your account safe and secure. For your protection, please do not share your password with anyone.

  If you forget the password provided to you, or if for any reason you are unable to access your Moodlink account,
  please reach out to the Guidance and Counseling Unit so they can verify your identity and help you reset your
  password or restore your access. The GCU will be the one to assist you with any account or log-in concerns.

  For other comments, questions, or inquiries, you may reach us through
  [support@moodlink.com](mailto:support@moodlink.com). You may also contact us through our official Moodlink Facebook
  page, where you can send us a message and get updates about Moodlink.

  Thank you for being part of Moodlink!

  Regards,<br>
  The Moodlink Team
</x-mail::message>