import {Outlet} from "react-router-dom";

export default function GuestLayout() {
  return (
    <div>
      Part of layout
      <Outlet/>
    </div>
  );
}

