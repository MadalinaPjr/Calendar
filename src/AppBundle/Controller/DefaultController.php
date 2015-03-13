<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Event;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction()
    {
        return $this->render('AppBundle::welcomeScreen.html.twig');
    }

    /**
     * @Route("/monthly/{month}/{year}", name="monthly", defaults={"month" = 0, "year" = 0})
     */
    public function monthlyAction(Request $request, $month, $year)
    {
        $createEventForm = $this->createEventForm($request);

        if ($month == 0 || $year == 0) {
            $now = new \DateTime("now");
            $month = $now->format("m");
            $year = $now->format("Y");
        } else {
            $now = new \DateTime("1-" . $month . "-" . $year);
        }

        $calendarCells = $this->getCalendarCells($now);

        $days = $calendarCells['noOfFullCells'];

        $events = $this->getEvents($days, $now);

        $weekdays = [
            'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'
        ];

        $prevDate =  date_create($now->format("d-m-Y") . " first day of last month");
        $nextDate =  date_create($now->format("d-m-Y") . " first day of next month");

        return $this->render('AppBundle::monthly.html.twig', [
            'cells' => $calendarCells,
            'weekdays' => $weekdays,
            'events' => $events,
            'monthName' => $this->getMonthName($month),
            'year' => $year,
            'prevDate' => $prevDate,
            'nextDate' => $nextDate,
            'form' => $createEventForm->createView()
        ]);

    }

    /**
     * @Route("/event/{id}/edit", name="edit_event")
     */
    public function eventAction(Request $request, $id)
    {
        $event = $this->getDoctrine()->getRepository("AppBundle:Event")->find($id);

        $form = $this->createEventForm($request, $event);

        return $this->render('AppBundle::eventEdit.html.twig', [
            'form' => $form->createView(),
            'event' => $event
        ]);
    }

    /**
     * @param $now
     * @return array
     */
    private function getCalendarCells($now)
    {

        $firstMonday = strtotime("first monday of " . $now->format("M Y"));
        $firstMonday = new \DateTime(date("d-m-Y", $firstMonday));

        $calendarCells['noOfEmptyCells'] = 7 - ($firstMonday->format("d") - 1);

        $lastDayOfMonth = strtotime("last day of " . $now->format("M Y"));
        $lastDayOfMonth = new \DateTime(date("d-m-Y", $lastDayOfMonth));

        $calendarCells['noOfFullCells'] = $lastDayOfMonth->format("d");

        $totalUsedDays = $calendarCells['noOfEmptyCells'] + (int)$calendarCells['noOfFullCells'];
        if ($totalUsedDays <= 28) {
            $totalNoOfCalendarCells = 28;
        } elseif ($totalUsedDays <= 35) {
            $totalNoOfCalendarCells = 35;
        } else {
            $totalNoOfCalendarCells = 42;
        }

        $calendarCells['noOfLastEmptyCells'] = $totalNoOfCalendarCells - ((int)$calendarCells['noOfFullCells'] + $calendarCells['noOfEmptyCells']);

        return $calendarCells;
    }

    private function getEvents($days, $now)
    {
	$em = $this->getDoctrine()->getManager();
	$query = $em->createQuery('select p from AppBundle:Event p '
		. 'where p.date between :start and :end')
		->setParameter('start', $now->format('Y-m-'). 1)
		->setParameter('end', $now->format('Y-m-') . $days);

	$events = $query->getResult();   
	$days = array_fill(1, $days, array());
	
	foreach($events as $value)
	{
	    $days[$value->getDate()->format('d')][] = $value;
	}	
        return $days;
    }

    private function getMonthName($month)
    {
        $months = ["January","February","March","April","May","June","July","August","September","October","November","December"];

        $monthName = $months[$month - 1];

        return $monthName;
    }

    private function createEventForm($request, $event = null)
    {
        if ($event == null) {
            $event = new Event();
        }

        $createEventForm = $this->createFormBuilder($event);
        $createEventForm->add('title');
        $createEventForm->add('description');
        $createEventForm->add('date');
        $createEventForm->add('priority', 'choice', [
            'choices' => [
                'active' => 'active',
                'success' => 'success',
                'info' => 'info',
                'warning' => 'warning',
                'danger' => 'danger'
            ]
        ]);
        $createEventForm = $createEventForm->getForm();

        $createEventForm->handleRequest($request);
        if ($createEventForm->isValid()) {
            $manager = $this->getDoctrine()->getManager();
            $manager->persist($event);
            $manager->flush();

            return new RedirectResponse($this->generateUrl('monthly'));
        }

        return $createEventForm;
    }

    /**
     * @Route("/event/{id}/delete", name="delete_event")
     */
    public function deleteEvent($id)
    {
        $event = $this->getDoctrine()->getRepository("AppBundle:Event")->find($id);

        $this->getDoctrine()->getManager()->remove($event);
        $this->getDoctrine()->getManager()->flush();

        return new RedirectResponse($this->generateUrl('monthly'));
    }
}
