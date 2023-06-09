import {Outlet} from "react-router-dom";
import logoSvg from "../../public/logo.svg"

export default function GuestLayout() {
  return (
    <div>
      <div className="flex min-h-full flex-1 flex-col justify-center px-6 py-12 lg:px-8">
        <div className="sm:mx-auto sm:w-full sm:max-w-sm">
          <img
            className="mx-auto h-10 w-auto"
            src={logoSvg}
            alt="Your Company"
          />
        </div>
        <Outlet/>
      </div>
    </div>
  );
}

