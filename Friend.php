<?php
class Friend
{
    public $FriendUserID;
    public $FriendUserName;
    public $FriendFirstName;
    public $FriendLastName;
    public $Steps;

    function __construct($FriendID, $FriendUserName, $FriendFirstName, $FriendLastName, $Steps)
    {
        $this->FriendUserID = $FriendID;
        $this->FriendUserName = $FriendUserName;
        $this->FriendFirstName = $FriendFirstName;
        $this->FriendLastName = $FriendLastName;
        $this->Steps = $Steps != null ? $Steps : 0000;
    }
    function Friends()
    {
        $userID = $_SESSION['userID'];
        $query  = "SELECT
                        u.UserName,
                        f.FriendID,
                        u.FirstName,
                        u.LastName,
                        s.steps
                    FROM `Friends` f
                    join Users u on u.UserID = f.FriendID
                    left join
                    (	select
                            s.UserID,
                            sum(s.Steps) as steps
                        from
                            Steps s
                        WHERE
                            (s.DateUpdated BETWEEN date_sub(now(), INTERVAL 7 day) AND now() OR
                            s.DateUpdated is null)
                    ) as s on s.UserID = u.UserID
                    WHERE
                        f.UserID = '$userID' AND f.Status = 'Accepted'";
        $results = $this->SubmitQuery($query);
        $FriendList[] = new Friend("","","","","");
        while($result = $results->fetch_array(MYSQLI_ASSOC))
        {
            $FriendList[] = new Friend($result['FriendID'],
                                       $result['UserName'],
                                       $result['FirstName'],
                                       $result['LastName'],
                                       $result['steps']);
        }
        $results->close();
        return $FriendList;
    }

    function PendingFriends($UserID)
    {
            $query  = "SELECT
                            u.UserName,
                            f.UserID,
                            u.FirstName,
                            u.LastName
                        FROM `Friends` f
                        join
                            Users u on u.UserID = f.UserID
                        WHERE
                            f.FriendID = $UserID AND
                            f.Status = 'Pending'";
            $results = $this->SubmitQuery($query);
            $PendingFriendsList[] =  new Friend("","","","","");
            while($result = $results->fetch_array(MYSQLI_ASSOC))
            {
                $PendingFriendsList[] = new Friend($result['UserID'], $result['UserName'], $result['FirstName'], $result['LastName'],"");
            }
            $results->close();
        return $PendingFriendsList;
    }

    function FindFriend($UserName)
    {
        $userID = $_SESSION['userID'];
          $query1  = "Select
                            u.UserID,
                            u.UserName,
                            u.FirstName,
                            u.LastName
                        from
                            Users u
                        where
                            u.UserName = '$UserName'";

          $FoundUser = $this->SubmitQuery($query1);

          if($FoundUser)
          {
                $FoundFriend = new Friend("","","","","");
                $result = $FoundUser->fetch_array(MYSQLI_ASSOC);

                $FoundUserID =  $result['UserID'];
                $query2 = "SELECT *
                          FROM `Friends`
                          WHERE Friends.UserID = $FoundUserID and Friends.FriendID = $userID";
                $Check1 = $this->SubmitQuery($query2);
                $CheckifExistsInFriendsTable = $Check1->fetch_array(MYSQLI_ASSOC);
                  if($CheckifExistsInFriendsTable)
                  {
                      $status = $CheckifExistsInFriendsTable['Status'];
                      echo "You have a request already from them with status: $status ";
                      return null;
                  }
                  $query3 = "SELECT *
                          FROM `Friends`
                          WHERE Friends.UserID = $userID and Friends.FriendID = $FoundUserID";
                  $Check2 = $this->SubmitQuery($query3);
                  $CheckifExistsInFriendsTableSent = $Check2->fetch_array(MYSQLI_ASSOC);
                  if($CheckifExistsInFriendsTableSent)
                  {
                      $status =$CheckifExistsInFriendsTableSent['Status'];
                      echo "You have sent a request already to them with status $status";
                      return null;
                  }
                $FoundFriend = new Friend($result['UserID'], $result['UserName'], $result['FirstName'], $result['LastName'], "");
                $FoundUser->close();
                return $FoundFriend;
          }
          $FoundUser->close();
          return null;
    }

    function SendFriendRequest($userID, $FriendID)
    {
        $query = "INSERT INTO `Friends`(`UserID`, `FriendID`)
                  VALUES ('$userID', '$FriendID')";
        $results = $this->SubmitQuery($query);
        return;
    }

    function AcceptFriendRequest($SendRequestUserID, $AcceptRequestFriendID)
    {
        $query = "UPDATE `Friends`
                  SET `Status`='Accepted',`UpdatedOn`= CURRENT_TIMESTAMP
                  WHERE `UserID` = $SendRequestUserID and `FriendID` = $AcceptRequestFriendID";
        $results = $this->SubmitQuery($query);
        //$results->close();

        $query2 = "INSERT INTO `Friends`(`UserID`, `FriendID`, `Status`)
                  VALUES ('$AcceptRequestFriendID', '$SendRequestUserID', 'Accepted')";
        $results2 = $this->SubmitQuery($query2);
        //$results2->close();
        return;
    }

    function DeclineFriendRequest($SendRequestUserID, $DeclineRequestFriendID)
    {
        $query = "UPDATE `Friends`
                  SET `Status`='Declined',`UpdatedOn`= CURRENT_TIMESTAMP
                  WHERE `UserID` = $SendRequestUserID and `FriendID` = $DeclineRequestFriendID";
        $results = $this->SubmitQuery($query);
        //$results->close();
        return;
    }

    function DeleteFriend($userID,$FriendID)
    {
        $query = "UPDATE `Friends`
                  SET `Status`='Removed',`UpdatedOn`= CURRENT_TIMESTAMP
                  WHERE `UserID` = $userID and `FriendID` = $FriendID";
        $results = $this->SubmitQuery($query);
        //$results->close();
        echo 1;
        $query2 = "UPDATE `Friends`
                  SET `Status`='Removed',`UpdatedOn`= CURRENT_TIMESTAMP
                  WHERE `UserID` = $FriendID and `FriendID` = $userID";
        $results2 = $this->SubmitQuery($query2);
        //$results2->close();
        return;
    }

    function SubmitQuery($query)
    {
        require 'login.php';
        $conn = new mysqli($hn, $un, $pw, $db);
        if ($conn->connect_error)
            die($conn->connect_error);
        $results = $conn->query($query);
        if (!$results) die ("Database access failed: " . $conn->error);
        $conn->close();
         return $results;
    }
}

?>
