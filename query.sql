select count(*) as today_visit
from leads l where date(l.visit_date)=current_date group by l.isInterested;

select date(visit_date) as date, count(*) as count, concat(u.fname," ", u.lname) as name
from leads
join users u on leads.entryBy = u.id
group by date(visit_date) , entryBy;

select l.name, l.contact, l.email, date(l.visit_date) as visitDate, concat(u.fname," ", u.lname) as name, l.interestedIn, l.isInterested
from leads l join users u on l.entryBy = u.id where visit_date between '2021-01-01' and '2021-01-23' order by date(visit_date) desc ;

select l.name, l.contact, l.email, date(l.visit_date) as visitDate, concat(u.fname," ", u.lname) as name, l.interestedIn, l.isInterested
from leads l join users u on l.entryBy = u.id  order by date(visit_date) desc ;
