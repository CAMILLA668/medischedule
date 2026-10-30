<?php 
namespace App\Services;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AppointmentService
{
    public function schedule(Doctor $doctor, User $user, Appointment $appointment)
    {
        return DB::transaction(function () use ($doctor, $user, $appointment) {

       $appointmentDate =  $appointment->appointment_date;

       if($appointmentDate->isPast()) {
          throw new \DomainException(
            'Não é possível marcar consulta em uma data que já passou.'
          );
       }

       $doctorAppointment = Appointment::where('doctor_id', $doctor->id)
          ->where('appointment_date', $appointmentDate)
          ->exists();

        $endDate = $appointmentDate->copy()->addMinutes($doctor->slot_duration);

        if($appointmentDate)

          if($doctorAppointment == true) {
            throw new \DomainException(
                'Esse horário já foi preenchido. Por favor, escolha outro horário.'
            );
        }

        $patientAppointment = Appointment::where('patient_id', $user->id)
           ->where('appointment_date', $appointmentDate)
           ->exists();

           if($patientAppointment == true) {
            throw new \DomainException(
                'Você já possuí uma consulta nesse horário. Por favor, escolha outro horário.'
                );
           }

        return Appointment::create([
            'patient_id'       => $user->id,
            'doctor_id'        => $doctor->id,
            'status'           => 'scheduled',
            'appointment_date' => $appointmentDate,
        ]);
    });

   }

    public function cancel(User $user, Appointment $appointment) {
        if($user->id !== $appointment->patient_id ) {
            abort(403);
        }

        if($appointment->isCanceled()) {
            throw new \DomainException(
                'Esta consulta já foi cancelada.'
            );
        }

        $appointmentDate = $appointment->appointment_date;
        $dateDiff = $appointmentDate->diffInHours(now(), true);

        
        if($appointmentDate->isPast()) {
            throw new \DomainException(
                'Consultas passadas não podem ser canceladas.'
            );
        }

        if($dateDiff < 24) {
            throw new \DomainException(
                'Consultas só podem ser canceladas com no mínimo 24 horas de antecedência em relação ao horário marcado.'
            );
        }

        $appointment->update([
            'status'      => 'canceled',
            'canceled_at' => now(),
        ]);
    }
}
?>